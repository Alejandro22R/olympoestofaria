<?php

namespace App\Domain\Reviews\Actions;

use App\Domain\Auth\Permission;
use App\Domain\Notifications\StaffNotifier;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Review;
use App\Models\WorkOrder;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El cliente califica un trabajo entregado. Una sola reseña por orden (puede corregirla mientras
 * no esté publicada). Nunca se publica sola: el taller la modera.
 */
class SubmitReview
{
    public function __construct(private readonly StaffNotifier $notifier) {}

    /**
     * @param  array{rating: int, comment?: ?string, consent_publish?: bool}  $data
     */
    public function execute(WorkOrder $order, array $data): Review
    {
        if ($order->status !== WorkOrderStatus::Delivered) {
            throw ValidationException::withMessages(['rating' => __('reviews.errors.not_delivered')]);
        }

        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => __('reviews.errors.rating')]);
        }

        $existing = $order->review;
        if ($existing?->is_published) {
            throw ValidationException::withMessages(['rating' => __('reviews.errors.already_published')]);
        }

        return DB::transaction(function () use ($order, $data, $rating, $existing): Review {
            $review = Review::query()->updateOrCreate(['work_order_id' => $order->id], [
                'customer_id' => $order->vehicle->customer_id,
                'rating' => $rating,
                'comment' => filled($data['comment'] ?? null) ? trim((string) $data['comment']) : null,
                'consent_publish' => (bool) ($data['consent_publish'] ?? false),
            ]);

            if ($existing === null) {
                $this->notifier->notify(Permission::CustomersManage, new StaffAlert(
                    type: 'review', titleKey: 'notifications.review_received',
                    params: ['number' => $order->number(), 'rating' => $rating],
                    url: ReviewResource::getUrl('edit', ['record' => $review]),
                    color: $rating >= 4 ? 'success' : 'warning', icon: 'heroicon-o-star',
                ));
            }

            return $review;
        });
    }
}

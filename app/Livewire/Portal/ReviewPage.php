<?php

namespace App\Livewire\Portal;

use App\Domain\Reviews\Actions\SubmitReview;
use App\Models\WorkOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Reseña del cliente tras la entrega (enlace firmado del aviso "¿cómo fue?").
 */
#[Layout('layouts.portal')]
class ReviewPage extends Component
{
    public WorkOrder $workOrder;

    public int $rating = 0;

    public ?string $comment = null;

    public bool $consent = false;

    public bool $sent = false;

    public function mount(WorkOrder $workOrder): void
    {
        Gate::authorize('track', $workOrder);
        $this->workOrder = $workOrder;

        if ($review = $workOrder->review) {
            $this->rating = $review->rating;
            $this->comment = $review->comment;
            $this->consent = $review->consent_publish;
            $this->sent = $review->is_published;
        }
    }

    public function save(SubmitReview $submit): void
    {
        Gate::authorize('track', $this->workOrder);
        $data = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'consent' => ['boolean'],
        ], [], ['rating' => __('reviews.fields.rating'), 'comment' => __('reviews.fields.comment')]);

        $submit->execute($this->workOrder, ['rating' => $data['rating'], 'comment' => $data['comment'], 'consent_publish' => $data['consent']]);
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.portal.review-page')->title(__('reviews.portal.title').' · Olympo');
    }
}

<?php

namespace App\Domain\Reviews\Actions;

use App\Models\Review;
use Illuminate\Validation\ValidationException;

/**
 * Moderación: solo se publica lo que el cliente autorizó. Queda en el registro de actividad.
 */
class PublishReview
{
    public function execute(Review $review, bool $publish): Review
    {
        if ($publish && ! $review->consent_publish) {
            throw ValidationException::withMessages(['is_published' => __('reviews.errors.no_consent')]);
        }

        $review->forceFill(['is_published' => $publish])->save();
        activity()->performedOn($review)->causedBy(auth()->user())->event($publish ? 'published' : 'unpublished')->log('review');

        return $review;
    }
}

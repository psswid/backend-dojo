<?php

namespace App\Services;

use App\Models\Review;

/**
 * SuperMemo-2 spaced-repetition scheduler.
 *
 * Maps a 0-5 quality-of-recall grade into the next review interval.
 * Used by the quiz engine so questions resurface until they reach mastery.
 */
class SpacedRepetition
{
    public const MIN_EASE = 1.3;

    /**
     * Apply an SM-2 review and persist the updated card.
     */
    public function review(Review $review, int $quality): Review
    {
        $quality = max(0, min(5, $quality));

        if ($quality < 3) {
            // Forgot: reset the interval, count a lapse.
            $review->repetitions = 0;
            $review->interval_days = 1;
            $review->lapses = ($review->lapses ?? 0) + 1;
            $review->state = 'learning';
        } else {
            if ($review->repetitions === 0) {
                $review->interval_days = 1;
                $review->state = 'learning';
            } elseif ($review->repetitions === 1) {
                $review->interval_days = 6;
                $review->state = 'review';
            } else {
                $review->interval_days = (int) round($review->interval_days * $review->ease_factor);
                $review->state = $review->interval_days >= 21 ? 'mastered' : 'review';
            }
            $review->repetitions = ($review->repetitions ?? 0) + 1;
        }

        // SM-2 ease factor update (always applied).
        $review->ease_factor = max(
            self::MIN_EASE,
            $review->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02))
        );

        $review->last_reviewed_at = now();
        $review->due_at = now()->addDays($review->interval_days);
        $review->save();

        return $review;
    }

    /**
     * Convenience: derive a quality grade from a simple correctness signal.
     */
    public function qualityFor(bool $correct): int
    {
        return $correct ? 5 : 1;
    }
}

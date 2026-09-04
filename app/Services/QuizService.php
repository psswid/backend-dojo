<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Review;
use App\Models\User;

/**
 * Records a quiz attempt and updates the spaced-repetition card.
 */
class QuizService
{
    public function __construct(private SpacedRepetition $spacedRepetition) {}

    /**
     * Persist an attempt and schedule the next review.
     */
    public function record(
        User $user,
        Question $question,
        array $answer,
        bool $correct,
        int $quality,
        int $timeTakenSeconds = 0
    ): Attempt {
        $attempt = Attempt::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'answer' => $answer,
            'is_correct' => $correct,
            'score' => $correct ? 1.0 : 0.0,
            'time_taken_seconds' => $timeTakenSeconds,
        ]);

        $review = Review::firstOrNew([
            'user_id' => $user->id,
            'question_id' => $question->id,
        ]);

        if (! $review->exists) {
            $review->ease_factor = 2.5;
            $review->state = 'new';
        }

        $this->spacedRepetition->review($review, $quality);

        return $attempt;
    }
}

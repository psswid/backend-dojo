<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Review;
use App\Models\Topic;
use App\Models\User;
use App\Services\SpacedRepetition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpacedRepetitionTest extends TestCase
{
    use RefreshDatabase;

    private SpacedRepetition $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new SpacedRepetition;
    }

    private function makeReview(): Review
    {
        $user = User::factory()->create();
        $topic = Topic::create(['slug' => 'test', 'name' => 'Test']);
        $question = Question::create([
            'topic_id' => $topic->id,
            'type' => 'single',
            'prompt' => 'Test?',
            'options' => ['A', 'B'],
            'correct' => [0],
            'explanation' => 'x',
        ]);

        return Review::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'ease_factor' => 2.5,
            'interval_days' => 0,
            'repetitions' => 0,
            'state' => 'new',
            'due_at' => now(),
        ]);
    }

    public function test_first_successful_review_moves_to_learning_with_one_day_interval(): void
    {
        $review = $this->scheduler->review($this->makeReview(), 5);

        $this->assertSame(1, $review->repetitions);
        $this->assertSame(1, $review->interval_days);
        $this->assertSame('learning', $review->state);
    }

    public function test_second_successful_review_sets_six_day_interval(): void
    {
        $review = $this->makeReview();
        $review->repetitions = 1;
        $review->save();

        $review = $this->scheduler->review($review, 5);

        $this->assertSame(2, $review->repetitions);
        $this->assertSame(6, $review->interval_days);
        $this->assertSame('review', $review->state);
    }

    public function test_failure_resets_interval_and_counts_lapse(): void
    {
        $review = $this->makeReview();
        $review->repetitions = 2;
        $review->interval_days = 6;
        $review->save();

        $review = $this->scheduler->review($review, 1);

        $this->assertSame(0, $review->repetitions);
        $this->assertSame(1, $review->interval_days);
        $this->assertSame(1, $review->lapses);
        $this->assertSame('learning', $review->state);
    }

    public function test_ease_factor_never_drops_below_minimum(): void
    {
        $review = $this->makeReview();
        $review->ease_factor = 1.3;
        $review->save();

        $review = $this->scheduler->review($review, 1);

        $this->assertSame(SpacedRepetition::MIN_EASE, $review->ease_factor);
    }

    public function test_long_interval_marks_question_mastered(): void
    {
        $review = $this->makeReview();
        $review->repetitions = 3;
        $review->interval_days = 10;
        $review->ease_factor = 2.5;
        $review->save();

        $review = $this->scheduler->review($review, 5);

        $this->assertSame('mastered', $review->state);
        $this->assertGreaterThanOrEqual(21, $review->interval_days);
    }
}

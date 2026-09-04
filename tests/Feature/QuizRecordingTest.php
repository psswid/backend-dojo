<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Review;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_an_attempt_creates_attempt_and_review_card(): void
    {
        $user = User::factory()->create();
        $topic = Topic::create(['slug' => 'queues', 'name' => 'Queues']);
        $question = Question::create([
            'topic_id' => $topic->id,
            'type' => 'single',
            'prompt' => 'What is a failed job?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct' => [1],
            'explanation' => 'A failed job is one that exhausted its retries.',
            'difficulty' => 'medium',
        ]);

        $service = app(QuizService::class);
        $attempt = $service->record($user, $question, [1], true, 5, 42);

        $this->assertTrue($attempt->is_correct);
        $this->assertSame(42, $attempt->time_taken_seconds);

        $review = Review::where('user_id', $user->id)->where('question_id', $question->id)->first();
        $this->assertNotNull($review);
        $this->assertSame(1, $review->repetitions);
        $this->assertNotNull($review->due_at);
    }

    public function test_wrong_answer_schedules_immediate_relearning(): void
    {
        $user = User::factory()->create();
        $topic = Topic::create(['slug' => 'cache', 'name' => 'Cache']);
        $question = Question::create([
            'topic_id' => $topic->id,
            'type' => 'single',
            'prompt' => 'Cache stampede?',
            'options' => ['A', 'B'],
            'correct' => [0],
            'explanation' => 'x',
        ]);

        $service = app(QuizService::class);
        $service->record($user, $question, [1], false, 1);

        $review = Review::where('user_id', $user->id)->where('question_id', $question->id)->first();
        $this->assertSame(0, $review->repetitions);
        $this->assertSame(1, $review->lapses);
    }
}

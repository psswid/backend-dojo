<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use App\Services\Llm\GapAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GapAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    private function topic(string $slug, string $name): Topic
    {
        return Topic::create(['slug' => $slug, 'name' => $name, 'color' => '#6366f1', 'icon' => '📘']);
    }

    public function test_recent_failures_returns_wrong_answers_with_topic(): void
    {
        $user = $this->user();
        $topic = $this->topic('queues-failed-jobs', 'Queues');
        $question = Question::create([
            'topic_id' => $topic->id, 'type' => 'open', 'prompt' => 'What is backoff?',
            'explanation' => 'x', 'difficulty' => 'medium',
        ]);

        Attempt::create(['user_id' => $user->id, 'question_id' => $question->id, 'is_correct' => false, 'score' => 0]);

        $failures = app(GapAnalyzer::class)->recentFailures($user);

        $this->assertCount(1, $failures);
        $this->assertSame('Queues', $failures->first()->topic);
        $this->assertSame('What is backoff?', $failures->first()->prompt);
    }

    public function test_task_performance_averages_scores_per_topic(): void
    {
        $user = $this->user();
        $topic = $this->topic('queues-failed-jobs', 'Queues');
        $task = Task::create([
            'topic_id' => $topic->id, 'type' => 'snippet', 'title' => 'Backoff',
            'description' => 'x', 'difficulty' => 'easy',
        ]);

        Attempt::create(['user_id' => $user->id, 'task_id' => $task->id, 'is_correct' => false, 'score' => 0.2]);
        Attempt::create(['user_id' => $user->id, 'task_id' => $task->id, 'is_correct' => true, 'score' => 1.0]);

        $performance = app(GapAnalyzer::class)->taskPerformance($user);

        $this->assertCount(1, $performance);
        $this->assertSame('Queues', $performance->first()->topic);
        $this->assertEqualsWithDelta(0.6, $performance->first()->avg, 0.001);
        $this->assertSame(2, $performance->first()->count);
    }

    public function test_summary_contains_module_names_and_counts(): void
    {
        $user = $this->user();
        $topic = $this->topic('queues-failed-jobs', 'Queues');
        $question = Question::create([
            'topic_id' => $topic->id, 'type' => 'open', 'prompt' => 'What is backoff?',
            'explanation' => 'x', 'difficulty' => 'medium',
        ]);

        Attempt::create(['user_id' => $user->id, 'question_id' => $question->id, 'is_correct' => false, 'score' => 0]);

        $summary = app(GapAnalyzer::class)->summary($user);

        $this->assertStringContainsString('Queues', $summary);
        $this->assertStringContainsString('Overall quiz attempts: 1', $summary);
        $this->assertStringContainsString('Recent wrong answers', $summary);
    }
}

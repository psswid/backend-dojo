<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response('ok', 200)]);
    }

    private function seedTask(string $type = 'snippet'): Task
    {
        $topic = Topic::create(['slug' => 'queues-failed-jobs', 'name' => 'Queues', 'color' => '#10b981', 'icon' => '📨']);

        return Task::create([
            'topic_id' => $topic->id,
            'type' => $type,
            'title' => 'Backoff delay',
            'description' => 'Write `backoff_delay(int $attempt): int`.',
            'starter_code' => $type === 'scenario' ? null : "function backoff_delay(int \$attempt): int {\n    // TODO\n}",
            'test_suite' => $type === 'scenario' ? null : "expect('a', backoff_delay(1), 2);",
            'solution' => $type === 'scenario' ? 'Model answer.' : "function backoff_delay(int \$attempt): int { return min(2 ** \$attempt, 3600); }",
            'rubric' => $type === 'scenario' ? ['criteria' => ['Isolation model'], 'key_points' => ['point']] : null,
            'difficulty' => 'easy',
            'tags' => ['queues'],
        ]);
    }

    public function test_task_index_and_session_render(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = $this->seedTask('snippet');

        $this->actingAs($user)->get('/tasks')->assertOk()->assertSee('Backoff delay');
        $this->actingAs($user)->get("/tasks/{$task->id}")->assertOk()->assertSee('solution.php');
    }

    public function test_scenario_task_renders_rubric(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = $this->seedTask('scenario');

        $this->actingAs($user)
            ->get("/tasks/{$task->id}")
            ->assertOk()
            ->assertSee('Isolation model')
            ->assertSee('Submit for AI review');
    }
}

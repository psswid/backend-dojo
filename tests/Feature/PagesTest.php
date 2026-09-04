<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Avoid a real LLM health probe during page renders.
        Http::fake(['*' => Http::response('ok', 200)]);
    }

    private function seedCurriculum(): Topic
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $topic = Topic::create([
            'slug' => 'queues-failed-jobs',
            'name' => 'Queues & Failed Jobs',
            'color' => '#10b981',
            'icon' => '📨',
            'sort_order' => 1,
        ]);
        Question::create([
            'topic_id' => $topic->id,
            'type' => 'single',
            'prompt' => 'What is a failed job?',
            'options' => ['A job that threw once', 'A job that exhausted its retries', 'A job with no handler', 'A paused job'],
            'correct' => [1],
            'explanation' => 'A failed job is one that exhausted its allowed attempts.',
            'difficulty' => 'medium',
            'tags' => ['queues'],
            'sort_order' => 1,
        ]);

        return $topic;
    }

    public function test_authenticated_pages_render(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $topic = $this->seedCurriculum();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Module mastery');

        $this->actingAs($user)
            ->get('/topics')
            ->assertOk()
            ->assertSee('Queues & Failed Jobs');

        $this->actingAs($user)
            ->get('/topics/queues-failed-jobs')
            ->assertOk()
            ->assertSee('What is a failed job?');

        $this->actingAs($user)
            ->get('/review')
            ->assertOk();
    }
}

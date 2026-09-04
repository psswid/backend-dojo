<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Resource;
use App\Models\Topic;
use App\Services\KnowledgeBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): Topic
    {
        $topic = Topic::create(['slug' => 'queues-failed-jobs', 'name' => 'Queues', 'color' => '#10b981', 'icon' => '📨']);

        Question::create([
            'topic_id' => $topic->id,
            'type' => 'open',
            'prompt' => 'How does exponential backoff work for failed jobs?',
            'explanation' => 'Failed jobs retry with a backoff delay that grows exponentially.',
            'difficulty' => 'medium',
            'tags' => ['queues', 'backoff'],
        ]);

        Resource::create([
            'topic_id' => $topic->id,
            'title' => 'Laravel Queues: retrying failed jobs',
            'url' => 'https://laravel.com/docs/queues#dealing-with-failed-jobs',
            'type' => 'docs',
            'summary' => 'Official docs on retries and backoff.',
        ]);

        return $topic;
    }

    public function test_search_returns_matching_question_and_resource(): void
    {
        $topic = $this->seedData();
        $kb = new KnowledgeBase;

        $results = $kb->search('backoff failed jobs', $topic->id);

        $this->assertNotEmpty($results['questions']);
        $this->assertSame('How does exponential backoff work for failed jobs?', $results['questions']->first()->prompt);
        $this->assertNotEmpty($results['resources']);
    }

    public function test_search_returns_empty_for_irrelevant_query(): void
    {
        $topic = $this->seedData();
        $kb = new KnowledgeBase;

        $results = $kb->search('blockchain smart contracts', $topic->id);

        $this->assertEmpty($results['questions']);
        $this->assertEmpty($results['resources']);
    }

    public function test_search_ignores_tokens_shorter_than_three_chars(): void
    {
        $topic = $this->seedData();
        $kb = new KnowledgeBase;

        $results = $kb->search('it is a to', $topic->id);

        $this->assertEmpty($results['questions']);
    }

    public function test_context_block_includes_question_and_resource(): void
    {
        $topic = $this->seedData();
        $kb = new KnowledgeBase;

        $block = $kb->contextBlock($kb->search('backoff', $topic->id));

        $this->assertStringContainsString('backoff', $block);
        $this->assertStringContainsString('Laravel Queues: retrying failed jobs', $block);
    }
}

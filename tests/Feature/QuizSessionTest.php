<?php

namespace Tests\Feature;

use App\Livewire\QuizSession;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Support\QuizText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizSessionTest extends TestCase
{
    use RefreshDatabase;

    private function seedTopic(int $questionCount = 3): Topic
    {
        $topic = Topic::create([
            'slug' => 'queues-failed-jobs',
            'name' => 'Queues & Failed Jobs',
            'color' => '#10b981',
            'icon' => '📨',
            'sort_order' => 1,
        ]);

        for ($i = 0; $i < $questionCount; $i++) {
            Question::create([
                'topic_id' => $topic->id,
                'type' => 'single',
                'prompt' => "Question number {$i}?",
                'options' => ['A', 'B', 'C', 'D'],
                'correct' => [1],
                'explanation' => "Explanation for {$i}.",
                'difficulty' => 'medium',
                'tags' => ['queues'],
                'sort_order' => $i,
            ]);
        }

        return $topic;
    }

    public function test_interrupted_topic_session_resumes_from_where_it_stopped(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $topic = $this->seedTopic();

        $this->actingAs($user);

        // First visit: answer Q1 (wrong), move to Q2, then "leave".
        $first = Livewire::test(QuizSession::class, ['topic' => $topic]);
        $first->call('selectOption', 3)
            ->call('answer')
            ->call('next')
            ->assertSet('index', 1);

        // Second visit (fresh Livewire instance = page reopened).
        Livewire::test(QuizSession::class, ['topic' => $topic])
            ->assertSet('resumed', true)
            ->assertSet('index', 1)
            ->assertSet('sessionTotal', 1)
            ->assertSet('questionIds', $first->get('questionIds'));
    }

    public function test_finished_session_is_not_resumed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $topic = $this->seedTopic(1);

        $this->actingAs($user);

        Livewire::test(QuizSession::class, ['topic' => $topic])
            ->call('selectOption', 1)
            ->call('answer')
            ->call('next')
            ->assertSet('finished', true);

        // Reopening after a completed session starts fresh.
        Livewire::test(QuizSession::class, ['topic' => $topic])
            ->assertSet('resumed', false)
            ->assertSet('index', 0)
            ->assertSet('sessionTotal', 0);
    }

    public function test_restart_clears_resumed_session(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $topic = $this->seedTopic();

        $this->actingAs($user);

        Livewire::test(QuizSession::class, ['topic' => $topic])
            ->call('selectOption', 3)
            ->call('answer')
            ->call('next')
            ->call('restart')
            ->assertSet('resumed', false)
            ->assertSet('index', 0)
            ->assertSet('sessionTotal', 0);

        // And a subsequent visit does not resurrect the abandoned session.
        Livewire::test(QuizSession::class, ['topic' => $topic])
            ->assertSet('resumed', false)
            ->assertSet('index', 0);
    }

    public function test_open_question_answer_text_survives_interruption(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $topic = Topic::create(['slug' => 'sql', 'name' => 'SQL']);
        Question::create([
            'topic_id' => $topic->id,
            'type' => 'open',
            'prompt' => 'Explain indexes?',
            'explanation' => "A B-tree index …\n\nKeep it short.",
            'difficulty' => 'medium',
        ]);

        $this->actingAs($user);

        Livewire::test(QuizSession::class, ['topic' => $topic])
            ->set('openAnswer', 'Partial draft answer')
            ->assertSet('openAnswer', 'Partial draft answer');

        Livewire::test(QuizSession::class, ['topic' => $topic])
            ->assertSet('resumed', true)
            ->assertSet('openAnswer', 'Partial draft answer');
    }

    public function test_quiz_text_preserves_all_prompt_content(): void
    {
        $prompts = [
            'Plain single question?',
            'Scenario lead. (a) First part? (b) Second part? (c) Third part?',
            'Inline `code` span with quotes "x" and ampersand & more.',
        ];

        foreach ($prompts as $prompt) {
            $html = '';
            foreach (QuizText::promptBlocks($prompt) as $block) {
                if ($block['type'] === 'enum') {
                    foreach ($block['items'] as $item) {
                        // UI renders markers as letter badges; keep them for comparison.
                        $html .= "({$item['k']}) {$item['html']} ";
                    }
                } else {
                    $html .= $block['html'].' ';
                }
            }

            // strip_tags keeps code-span contents; quote/amp entities survive decode.
            $plain = preg_replace('/\s+/u', ' ', trim(strip_tags(html_entity_decode($html))));
            // Backticks are formatting markers (rendered as code spans), not content.
            $expected = preg_replace('/\s+/u', ' ', trim(str_replace('`', '', $prompt)));
            $this->assertSame($expected, $plain, "Prompt content changed: {$prompt}");
        }
    }

    public function test_quiz_text_escapes_html_and_keeps_code_spans(): void
    {
        $blocks = QuizText::promptBlocks('Use `DB::table(\'x\')` for <b>raw</b> queries & more.');
        $this->assertSame('p', $blocks[0]['type']);
        $this->assertStringContainsString('&lt;b&gt;raw&lt;/b&gt;', $blocks[0]['html']);
        $this->assertStringContainsString('&amp;', $blocks[0]['html']);
        $this->assertStringContainsString('<code class="quiz-code">DB::table(&#039;x&#039;)</code>', $blocks[0]['html']);
        $this->assertStringNotContainsString('`', $blocks[0]['html']);
    }

    public function test_enumeration_only_recognises_contiguous_a_b_c_markers(): void
    {
        $plain = 'Use (a) an index or (b) a cache. Which one wins?';
        $blocks = QuizText::promptBlocks($plain);
        $this->assertSame('p', $blocks[0]['type']);

        $scenario = 'Scenario here. (a) Do X? (b) Do Y? (c) Do Z?';
        $blocks = QuizText::promptBlocks($scenario);
        $this->assertSame('p', $blocks[0]['type']);
        $this->assertSame('enum', $blocks[1]['type']);
        $this->assertCount(3, $blocks[1]['items']);
    }
}

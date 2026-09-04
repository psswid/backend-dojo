<?php

namespace Tests\Feature;

use App\Livewire\AiMentor;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\Llm\LlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AiMentorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake(['*' => Http::response('{"status":"ok"}', 200)]);
    }

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_mentor_page_renders_personas(): void
    {
        $this->actingAs($this->user())
            ->get('/mentor')
            ->assertOk()
            ->assertSee('Mock Interviewer')
            ->assertSee('Gap Analyzer')
            ->assertSee('Explainer')
            ->assertSee('Evaluator')
            ->assertSee('wire:stream="assistant-stream"', false)
            ->assertSee('wire:loading.flex', false);
    }

    public function test_mentor_page_requires_auth(): void
    {
        $this->get('/mentor')->assertRedirect('/login');
    }

    public function test_switch_persona_changes_active_persona(): void
    {
        $this->actingAs($this->user());

        Livewire::test(AiMentor::class)
            ->assertSet('persona', 'explainer')
            ->call('switchPersona', 'interviewer')
            ->assertSet('persona', 'interviewer');
    }

    public function test_send_persists_user_and_streamed_assistant_messages(): void
    {
        $this->actingAs($this->user());

        $llm = $this->mock(LlmService::class);
        $llm->shouldReceive('isOnline')->andReturn(false);
        $llm->shouldReceive('chatStream')->andReturnUsing(function () {
            yield 'Hello';
            yield ' from the mentor';
        });

        Livewire::test(AiMentor::class)
            ->set('draft', 'Explain queues')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('ai_conversations', 1);
        $this->assertDatabaseCount('ai_messages', 2);

        $conversation = AiConversation::first();
        $this->assertSame('explainer', $conversation->persona);
        $this->assertSame('Explain queues', $conversation->title);

        $assistant = AiMessage::where('role', 'assistant')->first();
        $this->assertSame('Hello from the mentor', $assistant->content);
    }

    public function test_send_records_error_message_when_llm_throws(): void
    {
        $this->actingAs($this->user());

        $llm = $this->mock(LlmService::class);
        $llm->shouldReceive('isOnline')->andReturn(false);
        $llm->shouldReceive('chatStream')->andThrow(new \RuntimeException('cluster down'));

        Livewire::test(AiMentor::class)
            ->set('draft', 'Explain queues')
            ->call('send')
            ->assertSet('error', 'cluster down');

        $assistant = AiMessage::where('role', 'assistant')->first();
        $this->assertNotNull($assistant);
        $this->assertTrue($assistant->meta['error'] ?? false);
    }
}

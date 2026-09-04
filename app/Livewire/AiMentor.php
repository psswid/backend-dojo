<?php

namespace App\Livewire;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Insight;
use App\Models\Topic;
use App\Services\KnowledgeBase;
use App\Services\Llm\GapAnalyzer;
use App\Services\Llm\LlmService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

class AiMentor extends Component
{
    public string $persona = 'explainer';

    public ?int $conversationId = null;

    public ?int $topicId = null;

    public string $draft = '';

    public bool $streaming = false;

    public ?string $error = null;

    public function mount(): void
    {
        if (! array_key_exists($this->persona, config('personas.personas', []))) {
            $this->persona = (string) config('personas.default', 'explainer');
        }

        $this->loadLatestConversation();
    }

    // ---- Computed properties ----

    public function getPersonasProperty(): array
    {
        return config('personas.personas', []);
    }

    public function getCurrentPersonaProperty(): array
    {
        return $this->personas[$this->persona] ?? [];
    }

    public function getConversationsProperty()
    {
        return AiConversation::query()
            ->where('user_id', auth()->id())
            ->where('persona', $this->persona)
            ->withCount('messages')
            ->latest()
            ->get();
    }

    public function getConversationProperty(): ?AiConversation
    {
        return $this->conversationId
            ? AiConversation::where('user_id', auth()->id())->find($this->conversationId)
            : null;
    }

    public function getMessagesProperty()
    {
        return $this->conversation?->messages()->get() ?? collect();
    }

    public function getTopicsProperty()
    {
        return Topic::orderBy('sort_order')->get();
    }

    public function getOnlineProperty(): bool
    {
        return (bool) Cache::remember('llm.online', 60, fn () => app(LlmService::class)->isOnline());
    }

    // ---- Actions ----

    public function switchPersona(string $key): void
    {
        if ($this->streaming || ! array_key_exists($key, $this->personas)) {
            return;
        }

        $this->persona = $key;
        $this->topicId = null;
        $this->draft = '';
        $this->error = null;
        $this->loadLatestConversation();
    }

    public function newConversation(): void
    {
        if ($this->streaming) {
            return;
        }

        $this->conversationId = null;
        $this->draft = '';
        $this->error = null;
    }

    public function selectConversation(int $id): void
    {
        if ($this->streaming) {
            return;
        }

        $conversation = AiConversation::where('user_id', auth()->id())->find($id);

        if (! $conversation) {
            return;
        }

        $this->conversationId = $conversation->id;
        $this->persona = $conversation->persona;
        $this->topicId = null;
        $this->error = null;
    }

    public function send(): void
    {
        $content = trim($this->draft);

        if ($content === '' || $this->streaming) {
            return;
        }

        $this->ensureConversation($content);
        $this->persistMessage('user', $content);
        $this->draft = '';

        $system = $this->buildSystemPrompt($content);

        $this->streamReply($system, $this->buildHistory(), []);
    }

    public function runGapAnalysis(): void
    {
        if ($this->streaming || $this->persona !== 'gap_analyzer') {
            return;
        }

        $summary = app(GapAnalyzer::class)->summary(auth()->user());
        $userContent = "Here is my learning data:\n\n".$summary;

        $this->ensureConversation('Gap analysis');
        $this->persistMessage('user', $userContent);

        $system = (string) ($this->currentPersona['system'] ?? '');
        $full = $this->streamReply($system, $this->buildHistory(), ['gap_analysis' => true]);

        if ($full !== null) {
            Insight::create([
                'user_id' => auth()->id(),
                'type' => 'gap_analysis',
                'content' => ['text' => $full],
                'generated_at' => now(),
            ]);
        }
    }

    public function finishInterview(): void
    {
        if ($this->streaming || $this->persona !== 'interviewer' || ! $this->conversation) {
            return;
        }

        $system = (string) ($this->currentPersona['system'] ?? '')
            ."\n\nThe candidate has asked to finish. Produce the structured interview report now: "
            ."overall score /5, strengths, weaknesses by topic, and the top 3 things to study next.";

        $full = $this->streamReply($system, $this->buildHistory(), ['report' => true]);

        if ($full !== null) {
            Insight::create([
                'user_id' => auth()->id(),
                'topic_id' => $this->topicId,
                'type' => 'interview_report',
                'content' => ['text' => $full],
                'generated_at' => now(),
            ]);
        }
    }

    // ---- Internals ----

    private function buildSystemPrompt(string $query): string
    {
        $system = (string) ($this->currentPersona['system'] ?? '');

        if ($this->topicId && ($this->currentPersona['topic_filter'] ?? false)) {
            $topic = Topic::find($this->topicId);

            if ($topic) {
                $system .= "\n\nFocus area: {$topic->name}.".($topic->description ? "\n{$topic->description}" : '');
            }
        }

        if ($this->persona === 'explainer') {
            $results = app(KnowledgeBase::class)->search($query, $this->topicId);

            if ($results['questions']->isNotEmpty() || $results['resources']->isNotEmpty()) {
                $system .= "\n\nRelevant curriculum context:\n".app(KnowledgeBase::class)->contextBlock($results);
            }
        }

        return $system;
    }

    /**
     * Stream an assistant reply into the fixed stream target, persist it, and
     * return the full text (or null when the model was unreachable).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $meta
     */
    private function streamReply(string $system, array $messages, array $meta): ?string
    {
        if ($this->streaming || ! $this->conversationId) {
            return null;
        }

        $this->streaming = true;
        $this->error = null;
        $full = '';

        try {
            // Prime the target and clear any leftover from a previous stream.
            $this->stream('assistant-stream', '', true);

            foreach (app(LlmService::class)->chatStream($messages, $system, $this->maxTokens()) as $delta) {
                $full .= $delta;
                $this->stream('assistant-stream', e($delta));
            }

            $this->persistMessage('assistant', $full, $meta);

            return $full;
        } catch (Throwable $e) {
            $msg = '⚠️ AI mentor unavailable: '.$e->getMessage();
            $this->stream('assistant-stream', e($msg));
            $this->persistMessage('assistant', $msg, ['error' => true]);
            $this->error = $e->getMessage();

            return null;
        } finally {
            $this->streaming = false;
        }
    }

    private function ensureConversation(?string $title = null): AiConversation
    {
        if ($this->conversationId) {
            $conversation = AiConversation::where('user_id', auth()->id())->find($this->conversationId);

            if ($conversation) {
                $this->persona = $conversation->persona;

                return $conversation;
            }
        }

        $conversation = AiConversation::create([
            'user_id' => auth()->id(),
            'persona' => $this->persona,
            'title' => $title ? Str::limit($title, 60) : ucfirst($this->persona),
        ]);

        $this->conversationId = $conversation->id;

        return $conversation;
    }

    private function persistMessage(string $role, string $content, array $meta = []): void
    {
        AiMessage::create([
            'conversation_id' => $this->conversationId,
            'role' => $role,
            'content' => $content,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildHistory(int $limit = 40): array
    {
        $messages = $this->conversation?->messages()->latest('id')->limit($limit)->get() ?? collect();

        return $messages
            ->reverse()
            ->filter(fn (AiMessage $m) => $m->role !== 'system' && ! ($m->meta['error'] ?? false))
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();
    }

    private function maxTokens(): int
    {
        return match ($this->persona) {
            'interviewer' => 1600,
            'evaluator', 'gap_analyzer' => 900,
            default => 1200,
        };
    }

    private function loadLatestConversation(): void
    {
        $this->conversationId = AiConversation::query()
            ->where('user_id', auth()->id())
            ->where('persona', $this->persona)
            ->latest()
            ->value('id');
    }

    public function render()
    {
        return view('livewire.ai-mentor', [
            'personas' => $this->personas,
            'conversations' => $this->conversations,
            'conversation' => $this->conversation,
            'messages' => $this->messages,
            'topics' => $this->topics,
            'online' => $this->online,
            'currentPersona' => $this->currentPersona,
        ]);
    }
}

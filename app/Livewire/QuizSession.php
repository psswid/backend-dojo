<?php

namespace App\Livewire;

use App\Models\Question;
use App\Models\QuizSession as QuizSessionRecord;
use App\Models\Review;
use App\Models\Topic;
use App\Services\QuizService;
use Livewire\Component;

class QuizSession extends Component
{
    public ?int $topicId = null;

    public string $title = '';

    /** @var int[] ids of the questions in this session, in order */
    public array $questionIds = [];

    public int $index = 0;

    public array $selected = [];

    public string $openAnswer = '';

    public bool $revealed = false;

    public ?bool $correct = null;

    public ?int $openQuality = null;

    public int $sessionCorrect = 0;

    public int $sessionTotal = 0;

    public bool $finished = false;

    public int $startedAt = 0;

    /** True when mount() resumed a previously interrupted session. */
    public bool $resumed = false;

    public function mount(?Topic $topic = null): void
    {
        $this->topicId = $topic?->id;
        $this->title = $topic?->name ?? 'Due Review';

        if (! $this->restoreSession()) {
            $this->startedAt = time();
            $this->loadQuestions();
        }
    }

    // ---- session persistence ------------------------------------------------

    protected function scope(): string
    {
        return $this->topicId ? 'topic' : 'review';
    }

    protected function topicKey(): int
    {
        return $this->topicId ?? 0;
    }

    protected function findSession(): ?QuizSessionRecord
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        return QuizSessionRecord::query()
            ->where('user_id', $userId)
            ->where('scope', $this->scope())
            ->where('topic_key', $this->topicKey())
            ->first();
    }

    /** Try to resume a stored in-progress session. Returns true when resumed. */
    protected function restoreSession(): bool
    {
        $record = $this->findSession();

        if (! $record || $record->finished) {
            return false;
        }

        $state = $record->state;

        $ids = array_values(array_filter(array_map('intval', $state['questionIds'] ?? [])));
        $total = count($ids);

        if ($total === 0) {
            return false;
        }

        // Drop ids that no longer exist, preserving the stored order (defensive;
        // seeders never delete rows, so this normally is a no-op).
        $existing = Question::whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $existing = array_flip($existing);
        $ids = array_values(array_filter($ids, fn ($id) => isset($existing[$id])));
        $total = count($ids);

        if ($total === 0) {
            return false;
        }

        $this->questionIds = $ids;
        $this->index = min(max((int) ($state['index'] ?? 0), 0), $total - 1);
        $this->selected = array_map('intval', $state['selected'] ?? []);
        $this->openAnswer = (string) ($state['openAnswer'] ?? '');
        $this->revealed = (bool) ($state['revealed'] ?? false);
        $this->correct = isset($state['correct']) ? (bool) $state['correct'] : null;
        $this->openQuality = isset($state['openQuality']) ? (int) $state['openQuality'] : null;
        $this->sessionCorrect = (int) ($state['sessionCorrect'] ?? 0);
        $this->sessionTotal = (int) ($state['sessionTotal'] ?? 0);
        $this->startedAt = (int) ($state['startedAt'] ?? time());
        $this->resumed = true;

        return true;
    }

    protected function snapshot(): array
    {
        return [
            'questionIds' => $this->questionIds,
            'index' => $this->index,
            'selected' => $this->selected,
            'openAnswer' => $this->openAnswer,
            'revealed' => $this->revealed,
            'correct' => $this->correct,
            'openQuality' => $this->openQuality,
            'sessionCorrect' => $this->sessionCorrect,
            'sessionTotal' => $this->sessionTotal,
            'startedAt' => $this->startedAt,
        ];
    }

    protected function persistSession(): void
    {
        $userId = auth()->id();

        if (! $userId) {
            return;
        }

        QuizSessionRecord::updateOrCreate(
            ['user_id' => $userId, 'scope' => $this->scope(), 'topic_key' => $this->topicKey()],
            ['state' => $this->snapshot(), 'finished' => $this->finished]
        );
    }

    protected function deleteSession(): void
    {
        $this->findSession()?->delete();
    }

    public function getCurrentQuestionProperty(): ?Question
    {
        $id = $this->questionIds[$this->index] ?? null;

        return $id ? Question::with('topic')->find($id) : null;
    }

    public function getProgressProperty(): string
    {
        $total = count($this->questionIds);

        return $total > 0 ? ($this->index + 1).' / '.$total : '0 / 0';
    }

    public function answer(QuizService $quizService): void
    {
        $question = $this->currentQuestion;

        if (! $question || $this->revealed || ! $question->isChoice() || empty($this->selected)) {
            return;
        }

        $correct = $this->isSelectionCorrect($question, $this->selected);
        $this->revealed = true;
        $this->correct = $correct;
        $this->sessionTotal++;
        $this->record($quizService, $question, $this->selected, $correct, $correct ? 5 : 1);
        $this->persistSession();
    }

    public function reveal(): void
    {
        $question = $this->currentQuestion;

        if (! $question || $this->revealed || ! $question->isOpen()) {
            return;
        }

        $this->revealed = true;
        $this->persistSession();
    }

    public function grade(QuizService $quizService, int $quality): void
    {
        $question = $this->currentQuestion;

        if (! $question || $this->openQuality !== null || ! $question->isOpen()) {
            return;
        }

        $quality = max(1, min(5, $quality));
        $this->openQuality = $quality;
        $this->correct = $quality >= 3;
        $this->sessionTotal++;
        $this->record($quizService, $question, ['text' => $this->openAnswer], $this->correct, $quality);
        $this->persistSession();
    }

    public function selectOption(int $i): void
    {
        $this->selected = [$i];
        $this->persistSession();
    }

    public function toggleOption(int $i): void
    {
        if (in_array($i, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$i]));
        } else {
            $this->selected[] = $i;
        }

        $this->persistSession();
    }

    public function updatedOpenAnswer(): void
    {
        $this->persistSession();
    }

    public function next(): void
    {
        if ($this->index + 1 >= count($this->questionIds)) {
            $this->finished = true;
            $this->deleteSession();

            return;
        }

        $this->index++;
        $this->selected = [];
        $this->openAnswer = '';
        $this->revealed = false;
        $this->correct = null;
        $this->openQuality = null;
        $this->persistSession();
    }

    public function restart(): void
    {
        $this->deleteSession();
        $this->resumed = false;
        $this->startedAt = time();
        $this->loadQuestions();
    }

    private function loadQuestions(): void
    {
        $user = auth()->user();

        if ($this->topicId) {
            $this->questionIds = Topic::find($this->topicId)?->questions()->pluck('id')
                ->map(fn ($id) => (int) $id)->all() ?? [];
        } else {
            $this->questionIds = Review::query()
                ->where('user_id', $user->id)
                ->where('due_at', '<=', now())
                ->where('state', '!=', 'mastered')
                ->orderBy('due_at')
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)->all();
        }

        $this->index = 0;
        $this->selected = [];
        $this->openAnswer = '';
        $this->revealed = false;
        $this->correct = null;
        $this->openQuality = null;
        $this->sessionCorrect = 0;
        $this->sessionTotal = 0;
        $this->finished = false;
    }

    private function record(QuizService $quizService, Question $question, array $answer, bool $correct, int $quality): void
    {
        $quizService->record(
            auth()->user(),
            $question,
            $answer,
            $correct,
            $quality,
            time() - $this->startedAt
        );

        if ($correct) {
            $this->sessionCorrect++;
        }
    }

    private function isSelectionCorrect(Question $question, array $selected): bool
    {
        $correct = array_map('intval', $question->correct ?? []);
        $selected = array_map('intval', $selected);

        sort($correct);
        sort($selected);

        return $correct === $selected;
    }

    public function render()
    {
        return view('livewire.quiz-session', [
            'question' => $this->currentQuestion,
        ]);
    }
}

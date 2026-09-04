<?php

namespace App\Livewire;

use App\Models\Question;
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

    public function mount(?Topic $topic = null): void
    {
        $this->topicId = $topic?->id;
        $this->title = $topic?->name ?? 'Due Review';
        $this->startedAt = time();
        $this->loadQuestions();
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
    }

    public function reveal(): void
    {
        $question = $this->currentQuestion;

        if (! $question || $this->revealed || ! $question->isOpen()) {
            return;
        }

        $this->revealed = true;
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
    }

    public function selectOption(int $i): void
    {
        $this->selected = [$i];
    }

    public function toggleOption(int $i): void
    {
        if (in_array($i, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$i]));
        } else {
            $this->selected[] = $i;
        }
    }

    public function next(): void
    {
        if ($this->index + 1 >= count($this->questionIds)) {
            $this->finished = true;

            return;
        }

        $this->index++;
        $this->selected = [];
        $this->openAnswer = '';
        $this->revealed = false;
        $this->correct = null;
        $this->openQuality = null;
    }

    public function restart(): void
    {
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

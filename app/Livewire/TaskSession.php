<?php

namespace App\Livewire;

use App\Models\Attempt;
use App\Models\Draft;
use App\Models\Task;
use App\Services\CodeRunner;
use App\Services\Llm\TaskEvaluator;
use Livewire\Component;

class TaskSession extends Component
{
    public ?int $taskId = null;

    public string $code = '';

    public string $answer = '';

    public ?string $output = null;

    public ?bool $passed = null;

    public bool $submitted = false;

    public ?string $aiFeedback = null;

    public ?int $aiScore = null;

    public bool $evaluating = false;

    public ?string $hint = null;

    public bool $hintLoading = false;

    /** @var 'saved'|'restored'|null */
    public ?string $draftState = null;

    public ?string $draftSavedAt = null;

    public function mount(?Task $task = null): void
    {
        $this->taskId = $task?->id;

        if ($task && $task->starter_code !== null) {
            $this->code = $task->starter_code;
        }

        // Resume a previous session's work so a refresh does not wipe the draft.
        if ($task && ($draft = $this->findDraft($task)) !== null) {
            $payload = $draft->payload ?? [];

            if ($task->type === 'scenario') {
                if (! empty($payload['answer'])) {
                    $this->answer = $payload['answer'];
                }
            } elseif (! empty($payload['code'])) {
                $this->code = $payload['code'];
            }

            $this->draftState = 'restored';
            $this->draftSavedAt = $draft->updated_at?->format('H:i');
        }
    }

    public function getCurrentTaskProperty(): ?Task
    {
        return $this->taskId ? Task::with('topic')->find($this->taskId) : null;
    }

    // ---- draft persistence -------------------------------------------------

    /** Livewire hook: fired when `code` arrives from the debounced autosave. */
    public function updatedCode(): void
    {
        $this->persistDraft();
    }

    /** Livewire hook: fired when `answer` arrives from the debounced autosave. */
    public function updatedAnswer(): void
    {
        $this->persistDraft();
    }

    public function saveDraft(): void
    {
        $this->persistDraft();
    }

    public function resetDraft()
    {
        $task = $this->currentTask;

        if ($task) {
            Draft::where('user_id', $this->currentUserId())->where('task_id', $task->id)->delete();
        }

        return redirect(request()->url());
    }

    private function findDraft(Task $task): ?Draft
    {
        $userId = $this->currentUserId();

        return $userId ? Draft::where('user_id', $userId)->where('task_id', $task->id)->first() : null;
    }

    private function persistDraft(): void
    {
        $task = $this->currentTask;

        if (! $task || ! ($userId = $this->currentUserId())) {
            return;
        }

        $content = $task->type === 'scenario' ? $this->answer : $this->code;

        if (trim($content) === '') {
            return;
        }

        Draft::updateOrCreate(
            ['user_id' => $userId, 'task_id' => $task->id],
            ['payload' => $task->type === 'scenario' ? ['answer' => $this->answer] : ['code' => $this->code]]
        );

        $this->draftState = 'saved';
        $this->draftSavedAt = now()->format('H:i:s');
    }

    private function currentUserId(): ?int
    {
        return auth()->id();
    }

    // ---- task actions --------------------------------------------------------

    public function runTests(CodeRunner $runner): void
    {
        $task = $this->currentTask;

        if (! $task || $task->type === 'scenario') {
            return;
        }

        $this->persistDraft();
        $result = $runner->run($this->code, (string) $task->test_suite);
        $this->output = $result['stdout'];
        $this->passed = $result['passed'];
    }

    public function submitCode(CodeRunner $runner): void
    {
        $task = $this->currentTask;

        if (! $task || $task->type === 'scenario') {
            return;
        }

        $this->persistDraft();
        $result = $runner->run($this->code, (string) $task->test_suite);
        $this->output = $result['stdout'];
        $this->passed = $result['passed'];
        $this->recordAttempt($result['passed'] ? 1.0 : 0.0, $result['passed']);
        $this->submitted = true;
    }

    public function submitScenario(TaskEvaluator $evaluator): void
    {
        $task = $this->currentTask;

        if (! $task || $task->type !== 'scenario' || trim($this->answer) === '') {
            return;
        }

        $this->persistDraft();
        $this->evaluating = true;
        $this->aiFeedback = null;

        try {
            $result = $evaluator->evaluateScenario($task, $this->answer);
            $this->aiFeedback = $result['feedback'];
            $this->aiScore = $result['score'];
            $this->recordAttempt((float) (($result['score'] ?? 0) / 5), ($result['score'] ?? 0) >= 3);
            $this->submitted = true;
        } catch (\Throwable $e) {
            $this->aiFeedback = "AI evaluator unavailable: {$e->getMessage()}";
        } finally {
            $this->evaluating = false;
        }
    }

    public function getHint(TaskEvaluator $evaluator): void
    {
        $task = $this->currentTask;

        if (! $task || $task->type === 'scenario') {
            return;
        }

        $this->hintLoading = true;
        $this->hint = null;

        try {
            $this->hint = $evaluator->hint($task, $this->code);
        } catch (\Throwable $e) {
            $this->hint = "AI hint unavailable: {$e->getMessage()}";
        } finally {
            $this->hintLoading = false;
        }
    }

    public function revealSolution(): void
    {
        $this->submitted = true;
    }

    private function recordAttempt(float $score, bool $correct): void
    {
        $task = $this->currentTask;

        if (! $task) {
            return;
        }

        Attempt::create([
            'user_id' => auth()->id(),
            'task_id' => $task->id,
            'answer' => $task->type === 'scenario' ? ['text' => $this->answer] : ['code' => $this->code],
            'is_correct' => $correct,
            'score' => $score,
        ]);
    }

    public function render()
    {
        return view('livewire.task-session', ['task' => $this->currentTask]);
    }
}

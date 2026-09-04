<?php

namespace App\Livewire;

use App\Models\Attempt;
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

    public function mount(?Task $task = null): void
    {
        $this->taskId = $task?->id;

        if ($task && $task->starter_code !== null) {
            $this->code = $task->starter_code;
        }
    }

    public function getCurrentTaskProperty(): ?Task
    {
        return $this->taskId ? Task::with('topic')->find($this->taskId) : null;
    }

    public function runTests(CodeRunner $runner): void
    {
        $task = $this->currentTask;

        if (! $task || $task->type === 'scenario') {
            return;
        }

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

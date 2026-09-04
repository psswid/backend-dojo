<?php

namespace App\Services\Llm;

use App\Models\Task;

/**
 * AI evaluation of coding tasks, backed by the local Qwen model.
 */
class TaskEvaluator
{
    public function __construct(private LlmService $llm) {}

    /**
     * Evaluate a free-text answer to a scenario/design task against its rubric.
     *
     * @return array{score: ?int, feedback: string}
     */
    public function evaluateScenario(Task $task, string $answer): array
    {
        $system = <<<'SYS'
You are a strict but fair senior Laravel/Symfony backend interviewer grading a candidate's
answer to a system-design question. First line must be exactly: SCORE: X/5  (X = 1..5).
Then give: a short verdict, what the candidate got right, what is missing or wrong,
and the most important concrete improvement. Be specific and technical. Keep under 250 words.
SYS;

        $prompt = "Task:\n{$task->description}\n\n"
            ."Rubric criteria:\n".implode("\n", array_map(fn ($c) => "- {$c}", $task->rubric['criteria'] ?? []))."\n\n"
            ."Candidate answer:\n{$answer}";

        $feedback = $this->llm->complete($prompt, $system, 900);

        return [
            'score' => $this->extractScore($feedback),
            'feedback' => $feedback,
        ];
    }

    /**
     * Produce a short hint for a snippet/debug task, without giving away the full solution.
     */
    public function hint(Task $task, string $code): string
    {
        $system = 'You are a senior PHP mentor. Give ONE short, specific hint (1-2 sentences) that '
            .'points at the bug or the missing technique WITHOUT writing the full solution.';

        $prompt = "Task: {$task->title}\n{$task->description}\n\nCurrent code:\n{$code}\n\nHint:";

        return $this->llm->complete($prompt, $system, 200);
    }

    private function extractScore(string $feedback): ?int
    {
        if (preg_match('/SCORE:\s*(\d)\s*\/\s*5/i', $feedback, $m)) {
            return max(1, min(5, (int) $m[1]));
        }

        return null;
    }
}

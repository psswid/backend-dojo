<?php

namespace App\Services\Llm;

use App\Models\Attempt;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Support\Collection;

/**
 * Gap analysis: aggregates a user's learning record into a deterministic
 * summary that the local model turns into a prioritized study plan.
 *
 * The aggregation (summary) is pure and unit-testable without an LLM; the
 * Livewire component feeds summary() to the model and persists the result
 * in `insights`.
 */
class GapAnalyzer
{
    public function __construct(private ProgressService $progress) {}

    /**
     * Deterministic data summary, ready to feed the LLM.
     */
    public function summary(User $user): string
    {
        $overall = $this->progress->overall($user);
        $stats = $this->progress->topicStats($user);

        $lines = [
            'Overall quiz attempts: '.$overall->attempts,
            'Overall accuracy: '.($overall->accuracy === null ? 'n/a' : $overall->accuracy.'%'),
            'Questions due for review: '.$overall->due,
            'Mastered questions: '.$overall->mastered,
            '',
            'Per-module accuracy (attempts -> accuracy%):',
        ];

        foreach ($stats as $stat) {
            $name = $stat->topic->name;
            $acc = $stat->accuracy === null ? 'not started' : $stat->accuracy.'%';
            $lines[] = "- {$name}: {$stat->attempts_total} attempts, {$acc}, {$stat->answered_count}/{$stat->question_count} answered, {$stat->due} due";
        }

        $failures = $this->recentFailures($user);
        if ($failures->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Recent wrong answers:';
            foreach ($failures as $f) {
                $lines[] = '- ['.$f->topic.'] '.$f->prompt;
            }
        }

        $tasks = $this->taskPerformance($user);
        if ($tasks->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Coding task results (average score 0..1):';
            foreach ($tasks as $t) {
                $lines[] = "- {$t->topic}: avg ".round($t->avg, 2)." over {$t->count} submission(s)";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return Collection<int, object{topic: string, prompt: string}>
     */
    public function recentFailures(User $user, int $limit = 8): Collection
    {
        return Attempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('question_id')
            ->where('is_correct', false)
            ->with(['question:id,prompt,topic_id', 'question.topic:id,name'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Attempt $a) => (object) [
                'topic' => $a->question?->topic?->name ?? 'Unknown',
                'prompt' => $a->question?->prompt ?? '(deleted question)',
            ]);
    }

    /**
     * @return Collection<int, object{topic: string, avg: float, count: int}>
     */
    public function taskPerformance(User $user): Collection
    {
        return Attempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('task_id')
            ->join('tasks', 'tasks.id', '=', 'attempts.task_id')
            ->join('topics', 'topics.id', '=', 'tasks.topic_id')
            ->selectRaw('topics.name as topic, AVG(attempts.score) as avg, COUNT(*) as count')
            ->groupBy('topics.name')
            ->orderBy('avg')
            ->get()
            ->map(fn ($row) => (object) [
                'topic' => $row->topic,
                'avg' => (float) $row->avg,
                'count' => (int) $row->count,
            ]);
    }
}

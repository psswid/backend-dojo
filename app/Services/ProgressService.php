<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Review;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aggregates learning progress for the dashboard heatmap and stats.
 */
class ProgressService
{
    /**
     * Per-topic stats for a user, in topic sort order.
     */
    public function topicStats(User $user): Collection
    {
        $topics = Topic::withCount('questions')->orderBy('sort_order')->get();

        $attempts = Attempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('question_id')
            ->join('questions', 'questions.id', '=', 'attempts.question_id')
            ->selectRaw('questions.topic_id, COUNT(*) as attempts_total, SUM(attempts.is_correct) as attempts_correct')
            ->groupBy('questions.topic_id')
            ->get()
            ->keyBy('topic_id');

        $answered = Attempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('question_id')
            ->join('questions', 'questions.id', '=', 'attempts.question_id')
            ->selectRaw('questions.topic_id, COUNT(DISTINCT questions.id) as distinct_answered')
            ->groupBy('questions.topic_id')
            ->get()
            ->keyBy('topic_id');

        $due = Review::query()
            ->where('user_id', $user->id)
            ->where('due_at', '<=', now())
            ->where('state', '!=', 'mastered')
            ->join('questions', 'questions.id', '=', 'reviews.question_id')
            ->selectRaw('questions.topic_id, COUNT(*) as due')
            ->groupBy('questions.topic_id')
            ->get()
            ->keyBy('topic_id');

        return $topics->map(function (Topic $topic) use ($attempts, $answered, $due) {
            $a = $attempts->get($topic->id);
            $total = $a->attempts_total ?? 0;
            $correct = (int) ($a->attempts_correct ?? 0);
            $answeredCount = (int) ($answered->get($topic->id)->distinct_answered ?? 0);

            return (object) [
                'topic' => $topic,
                'question_count' => $topic->questions_count,
                'attempts_total' => $total,
                'attempts_correct' => $correct,
                'accuracy' => $total > 0 ? round(100 * $correct / $total) : null,
                'answered_count' => $answeredCount,
                'due' => (int) ($due->get($topic->id)->due ?? 0),
            ];
        });
    }

    /**
     * Overall numbers for the dashboard header.
     */
    public function overall(User $user): object
    {
        $total = Attempt::where('user_id', $user->id)->whereNotNull('question_id')->count();
        $correct = Attempt::where('user_id', $user->id)->whereNotNull('question_id')->where('is_correct', true)->count();

        return (object) [
            'attempts' => $total,
            'correct' => $correct,
            'accuracy' => $total > 0 ? round(100 * $correct / $total) : null,
            'due' => Review::where('user_id', $user->id)->where('due_at', '<=', now())->where('state', '!=', 'mastered')->count(),
            'mastered' => Review::where('user_id', $user->id)->where('state', 'mastered')->count(),
        ];
    }
}

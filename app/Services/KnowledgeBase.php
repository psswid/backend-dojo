<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Lightweight retrieval over the curated curriculum (questions + resources).
 *
 * Used by the Explainer persona as a poor-man's RAG: tokenize the query and
 * score curriculum rows by how many tokens hit their searchable text. The
 * corpus is small (hundreds of rows), so an in-memory scan is fast, has no
 * external dependency, and is deterministic enough to unit-test. Meilisearch
 * can replace this later if the corpus grows.
 */
class KnowledgeBase
{
    /**
     * Search the curriculum for rows relevant to a free-text query.
     *
     * @return array{questions: Collection<int, object>, resources: Collection<int, object>}
     */
    public function search(string $query, ?int $topicId = null, int $limit = 5): array
    {
        $tokens = $this->tokenize($query);

        if ($tokens === []) {
            return ['questions' => collect(), 'resources' => collect()];
        }

        $questions = $this->scoreQuestions($tokens, $topicId, $limit);
        $resources = $this->scoreResources($tokens, $topicId, $limit);

        return ['questions' => $questions, 'resources' => $resources];
    }

    /**
     * Format search results into a compact prompt-context block.
     */
    public function contextBlock(array $results): string
    {
        $parts = [];

        foreach ($results['questions'] as $q) {
            $parts[] = "- [Question] {$q->prompt}\n  Answer/explanation: ".Str::limit($q->explanation, 400);
        }

        foreach ($results['resources'] as $r) {
            $parts[] = "- [Resource] {$r->title} ({$r->url})".($r->summary ? "\n  {$r->summary}" : '');
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return Collection<int, object>
     */
    private function scoreQuestions(array $tokens, ?int $topicId, int $limit): Collection
    {
        $query = Question::query()->with('topic:id,name');

        if ($topicId !== null) {
            $query->where('topic_id', $topicId);
        }

        return $query->get()
            ->map(function (Question $q) use ($tokens, $topicId) {
                $haystack = $this->haystack([
                    $q->prompt,
                    $q->explanation,
                    implode(' ', (array) ($q->tags ?? [])),
                ]);

                $hits = $this->hits($tokens, $haystack);

                return (object) [
                    'id' => $q->id,
                    'topic_id' => $q->topic_id,
                    'prompt' => $q->prompt,
                    'explanation' => $q->explanation,
                    'topic' => $q->topic?->name,
                    'hits' => $hits,
                ];
            })
            ->filter(fn ($item) => $item->hits > 0)
            ->sortByDesc('hits')
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<int, string>  $tokens
     * @return Collection<int, object>
     */
    private function scoreResources(array $tokens, ?int $topicId, int $limit): Collection
    {
        $query = Resource::query();

        if ($topicId !== null) {
            $query->where('topic_id', $topicId);
        }

        return $query->get()
            ->map(function (Resource $r) use ($tokens) {
                $haystack = $this->haystack([$r->title, (string) $r->summary]);

                return (object) [
                    'id' => $r->id,
                    'title' => $r->title,
                    'url' => $r->url,
                    'summary' => $r->summary,
                    'type' => $r->type,
                    'hits' => $this->hits($tokens, $haystack),
                ];
            })
            ->filter(fn ($item) => $item->hits > 0)
            ->sortByDesc('hits')
            ->take($limit)
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $query): array
    {
        $words = preg_split('/[^a-zA-Z0-9]+/', Str::lower($query)) ?: [];

        return array_values(array_unique(array_filter($words, fn ($w) => strlen($w) >= 3)));
    }

    /**
     * @param  array<int, ?string>  $parts
     */
    private function haystack(array $parts): string
    {
        return Str::lower(implode(' ', array_filter($parts, fn ($p) => $p !== null && $p !== '')));
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function hits(array $tokens, string $haystack): int
    {
        $hits = 0;

        foreach ($tokens as $token) {
            if (str_contains($haystack, $token)) {
                $hits++;
            }
        }

        return $hits;
    }
}

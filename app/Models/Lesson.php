<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'topic_id', 'slug', 'title', 'summary', 'minutes', 'difficulty', 'tags', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'tags' => 'array',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LessonSection::class)->orderBy('sort_order');
    }

    public function nextLesson(): ?Lesson
    {
        return static::query()
            ->where('topic_id', $this->topic_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();
    }

    public function prevLesson(): ?Lesson
    {
        return static::query()
            ->where('topic_id', $this->topic_id)
            ->where('sort_order', '<', $this->sort_order)
            ->orderByDesc('sort_order')
            ->first();
    }

    /**
     * Lessons of the same module whose concept tags overlap the given tags.
     */
    public static function matchingTags(int $topicId, ?array $tags): \Illuminate\Support\Collection
    {
        if (empty($tags)) {
            return collect();
        }

        return static::query()
            ->where('topic_id', $topicId)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Lesson $lesson) => ! empty(array_intersect((array) $lesson->tags, $tags)))
            ->values();
    }

    /**
     * Lessons that explain a given question's concepts (tag overlap).
     */
    public static function forQuestion(Question $question): \Illuminate\Support\Collection
    {
        return static::matchingTags($question->topic_id, $question->tags);
    }
}

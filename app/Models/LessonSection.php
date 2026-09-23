<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSection extends Model
{
    protected $fillable = [
        'lesson_id', 'key', 'kind', 'title', 'payload', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * DOM anchor used by the in-lesson table of contents.
     */
    public function anchor(): string
    {
        return 'section-'.$this->key;
    }
}

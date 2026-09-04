<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'topic_id', 'type', 'title', 'description', 'starter_code',
        'test_suite', 'solution', 'rubric', 'difficulty', 'tags', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rubric' => 'array',
            'tags' => 'array',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}

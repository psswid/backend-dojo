<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id', 'question_id', 'ease_factor', 'interval_days',
        'repetitions', 'lapses', 'state', 'due_at', 'last_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'ease_factor' => 'float',
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

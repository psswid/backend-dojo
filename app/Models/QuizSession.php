<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizSession extends Model
{
    protected $fillable = [
        'user_id', 'scope', 'topic_key', 'finished', 'state',
    ];

    protected function casts(): array
    {
        return [
            'finished' => 'boolean',
            'state' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

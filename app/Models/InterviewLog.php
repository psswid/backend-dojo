<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewLog extends Model
{
    protected $fillable = [
        'user_id', 'company', 'role', 'interview_date', 'raw_notes', 'structured',
    ];

    protected function casts(): array
    {
        return [
            'interview_date' => 'date',
            'structured' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

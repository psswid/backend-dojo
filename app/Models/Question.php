<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'topic_id', 'type', 'prompt', 'options', 'correct', 'explanation',
        'difficulty', 'tags', 'source', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct' => 'array',
            'tags' => 'array',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isChoice(): bool
    {
        return in_array($this->type, ['single', 'multi'], true);
    }

    public function isOpen(): bool
    {
        return $this->type === 'open';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Work-in-progress draft for a task (code or scenario answer), one per
 * user + task. Survives page refreshes so solutions can be built "na raty".
 */
class Draft extends Model
{
    protected $fillable = [
        'user_id', 'task_id', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}

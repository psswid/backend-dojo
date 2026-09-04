<?php

namespace App\Livewire;

use App\Models\Topic;
use Livewire\Component;

class TaskIndex extends Component
{
    public function render()
    {
        $topics = Topic::with(['tasks' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.task-index', ['topics' => $topics]);
    }
}

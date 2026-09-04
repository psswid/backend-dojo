<?php

namespace App\Livewire;

use App\Models\Topic;
use App\Services\ProgressService;
use Livewire\Component;

class TopicIndex extends Component
{
    public string $search = '';

    public function render(ProgressService $progress)
    {
        $topics = Topic::orderBy('sort_order')->get();

        return view('livewire.topic-index', [
            'topics' => $topics,
            'stats' => $progress->topicStats(auth()->user())->keyBy(fn ($s) => $s->topic->id),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Services\Llm\LlmService;
use App\Services\ProgressService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(ProgressService $progress, LlmService $llm)
    {
        $user = auth()->user();

        return view('livewire.dashboard', [
            'overall' => $progress->overall($user),
            'topics' => $progress->topicStats($user),
            'aiOnline' => Cache::remember('llm.online', 60, fn () => $llm->isOnline()),
        ]);
    }
}

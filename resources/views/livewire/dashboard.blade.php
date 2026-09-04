<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{-- AI agent status --}}
        <div class="mb-6 rounded-xl border p-4 flex items-center justify-between
            {{ $aiOnline ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200' }}">
            <div class="flex items-center gap-3">
                <span class="text-2xl">{{ $aiOnline ? '🤖' : '🛑' }}</span>
                <div>
                    <p class="font-semibold {{ $aiOnline ? 'text-emerald-800' : 'text-amber-800' }}">
                        {{ $aiOnline ? 'AI mentor online' : 'AI mentor offline' }}
                    </p>
                    <p class="text-sm {{ $aiOnline ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $aiOnline
                            ? 'Qwen3.6-35B-A3B is reachable via the local cluster.'
                            : 'Start the local cluster to enable AI features: ~/ai-cluster/cluster start' }}
                    </p>
                </div>
            </div>
            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $aiOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $aiOnline ? 'online' : 'offline' }}
            </span>
        </div>

        {{-- Overall stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5">
                <p class="text-sm text-gray-500">Total attempts</p>
                <p class="text-3xl font-bold text-gray-900">{{ $overall->attempts }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5">
                <p class="text-sm text-gray-500">Accuracy</p>
                <p class="text-3xl font-bold {{ $overall->accuracy === null ? 'text-gray-400' : ($overall->accuracy >= 70 ? 'text-emerald-600' : 'text-amber-600') }}">
                    {{ $overall->accuracy === null ? '—' : $overall->accuracy.'%' }}
                </p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5">
                <p class="text-sm text-gray-500">Due for review</p>
                <p class="text-3xl font-bold {{ $overall->due > 0 ? 'text-indigo-600' : 'text-gray-900' }}">{{ $overall->due }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5">
                <p class="text-sm text-gray-500">Mastered questions</p>
                <p class="text-3xl font-bold text-emerald-600">{{ $overall->mastered }}</p>
            </div>
        </div>

        @if ($overall->due > 0)
            <div class="mb-8 rounded-xl bg-indigo-50 border border-indigo-200 p-4 flex items-center justify-between">
                <p class="text-indigo-800">
                    <span class="font-semibold">{{ $overall->due }} question(s)</span> are due for spaced repetition.
                </p>
                <a href="{{ route('quiz.review') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500">Review now</a>
            </div>
        @endif

        {{-- Topic mastery heatmap --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Module mastery</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($topics as $stat)
                @php
                    $t = $stat->topic;
                    $accuracy = $stat->accuracy;
                    $pct = $t->question_count > 0 ? round(100 * $stat->answered_count / $t->question_count) : 0;
                @endphp
                <a href="{{ route('quiz.topic', $t->slug) }}" wire:navigate
                   class="bg-white shadow-sm rounded-xl p-5 hover:shadow-md transition border-l-4"
                   style="border-left-color: {{ $t->color }}">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ $t->icon }}</span>
                            <h3 class="font-semibold text-gray-900">{{ $t->name }}</h3>
                        </div>
                        @if ($stat->due > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">{{ $stat->due }} due</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 mb-2 text-sm">
                        <span class="font-mono text-gray-500">{{ $stat->answered_count }}/{{ $t->question_count }}</span>
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ $pct }}%; background-color: {{ $t->color }}"></div>
                        </div>
                    </div>

                    <p class="text-sm {{ $accuracy === null ? 'text-gray-400' : ($accuracy >= 70 ? 'text-emerald-600 font-semibold' : 'text-amber-600 font-semibold') }}">
                        {{ $accuracy === null ? 'Not started' : $accuracy.'% accuracy ('.$stat->attempts_total.' attempts)' }}
                    </p>
                </a>
            @endforeach
        </div>
    </div>
</div>

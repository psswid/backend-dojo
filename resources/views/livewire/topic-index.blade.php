<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($topics as $topic)
                @php
                    $stat = $stats->get($topic->id);
                    $accuracy = $stat->accuracy ?? null;
                    $pct = $topic->questions_count > 0 ? round(100 * ($stat->answered_count ?? 0) / $topic->questions_count) : 0;
                    $lessonCount = $topic->lessons_count ?? 0;
                @endphp
                <div class="bg-white shadow-sm rounded-2xl p-6 hover:shadow-md transition flex flex-col">
                    <a href="{{ route('quiz.topic', $topic->slug) }}" wire:navigate class="block flex-1">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-4xl">{{ $topic->icon }}</span>
                            @if (($stat->due ?? 0) > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">{{ $stat->due }} due</span>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $topic->name }}</h3>
                        <p class="text-sm text-gray-500 mb-4">{{ $topic->description }}</p>

                        <div class="flex items-center gap-3 mb-2 text-sm">
                            <span class="font-mono text-gray-500">{{ $stat->answered_count ?? 0 }}/{{ $topic->questions_count }}</span>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background-color: {{ $topic->color }}"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="{{ $accuracy === null ? 'text-gray-400' : ($accuracy >= 70 ? 'text-emerald-600 font-semibold' : 'text-amber-600 font-semibold') }}">
                                {{ $accuracy === null ? 'Not started' : $accuracy.'% accuracy' }}
                            </span>
                        </div>
                    </a>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between gap-2 text-sm">
                        <a href="{{ route('quiz.topic', $topic->slug) }}" wire:navigate
                           class="text-indigo-600 font-semibold hover:underline">Practice →</a>
                        @if ($lessonCount > 0)
                            <a href="{{ route('topics.lessons.index', $topic->slug) }}" wire:navigate
                               class="text-teal-700 font-semibold hover:underline">📖 {{ $lessonCount }} {{ $lessonCount === 1 ? 'lesson' : 'lessons' }}</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

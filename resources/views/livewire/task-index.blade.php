<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-10">
        @foreach ($topics as $topic)
            @if ($topic->tasks->isEmpty())
                @continue
            @endif
            <section>
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-2xl">{{ $topic->icon }}</span>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $topic->name }}</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($topic->tasks as $task)
                        <a href="{{ route('tasks.show', $task) }}" wire:navigate
                           class="bg-white shadow-sm rounded-2xl p-5 hover:shadow-md transition flex flex-col">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide
                                    {{ $task->type === 'scenario' ? 'bg-violet-100 text-violet-700' : ($task->type === 'debug' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $task->type }}
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-wide
                                    {{ $task->difficulty === 'easy' ? 'text-emerald-600' : ($task->difficulty === 'hard' ? 'text-red-600' : 'text-amber-600') }}">
                                    {{ $task->difficulty }}
                                </span>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">{{ $task->title }}</h3>
                            <p class="text-sm text-gray-500 flex-1">{{ Str::limit($task->description, 120) }}</p>
                            @if (! empty($task->tags))
                                <div class="mt-3 flex flex-wrap gap-1">
                                    @foreach (collect($task->tags)->take(4) as $tag)
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 text-[11px] font-mono">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <span class="mt-3 text-indigo-600 text-sm font-semibold">Solve →</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if ($topics->every(fn ($t) => $t->tasks->isEmpty()))
            <div class="bg-white shadow rounded-2xl p-10 text-center">
                <p class="text-gray-500">No coding tasks yet.</p>
            </div>
        @endif
    </div>
</div>

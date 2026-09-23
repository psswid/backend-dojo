<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $lesson->title }}
        </h2>
    </x-slot>

    <x-module-tabs :topic="$topic" active="lessons" :lesson-count="$topic->lessons()->count()" />

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Lesson body --}}
            <article class="bg-white shadow rounded-2xl p-6 sm:p-8">
                <div class="pb-5 mb-6 border-b border-gray-100">
                    <h1 class="text-2xl sm:text-[26px] font-bold text-gray-900 leading-9">{{ $lesson->title }}</h1>
                    @if ($lesson->summary)
                        <p class="mt-2 text-[15px] text-gray-600 leading-7">{{ $lesson->summary }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                        @if ($lesson->minutes)
                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 font-medium">🕒 ~{{ $lesson->minutes }} min</span>
                        @endif
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold uppercase tracking-wide
                            {{ $lesson->difficulty === 'easy' ? 'bg-emerald-100 text-emerald-700' : ($lesson->difficulty === 'hard' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $lesson->difficulty }}
                        </span>
                        @foreach ($lesson->tags ?? [] as $tag)
                            <span class="px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 font-medium border border-teal-100">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="lesson-body space-y-4">
                    @php $kinds = ['prose', 'heading', 'code', 'table', 'steps', 'callout', 'figure']; @endphp
                    @foreach ($lesson->sections as $section)
                        @if (in_array($section->kind, $kinds, true))
                            @include('lessons.partials.'.$section->kind, [
                                'section' => $section,
                                'payload' => $section->payload ?? [],
                            ])
                        @endif
                    @endforeach
                </div>
            </article>

            {{-- Practice block --}}
            @if ($relatedTasks->isNotEmpty() || $relatedQuestions->isNotEmpty())
                <div class="mt-6 bg-white shadow rounded-2xl p-6">
                    <h2 class="font-bold text-gray-900 text-lg mb-1">Put it to work</h2>
                    <p class="text-sm text-gray-500 mb-5">Concepts from this lesson also appear in these module drills and questions.</p>

                    <div class="grid gap-6 sm:grid-cols-2">
                        @if ($relatedTasks->isNotEmpty())
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Coding tasks</p>
                                <ul class="space-y-1.5">
                                    @foreach ($relatedTasks as $task)
                                        <li>
                                            <a href="{{ route('tasks.show', $task) }}" wire:navigate class="text-indigo-600 hover:underline text-sm leading-6">
                                                {{ $task->title }}
                                            </a>
                                            <span class="text-xs text-gray-400">· {{ $task->type }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($relatedQuestions->isNotEmpty())
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Quiz questions covering this</p>
                                <ul class="space-y-1.5">
                                    @foreach ($relatedQuestions as $question)
                                        <li class="text-sm text-gray-700 leading-6">{{ $question->prompt }}</li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('quiz.topic', $topic->slug) }}" wire:navigate
                                   class="inline-block mt-3 text-sm font-semibold text-teal-700 hover:underline">
                                    Open the {{ $topic->name }} quiz →
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Prev / next + back --}}
            <nav class="mt-6 flex items-stretch justify-between gap-3">
                @if ($prevLesson)
                    <a href="{{ route('topics.lessons.show', [$topic->slug, $prevLesson->slug]) }}" wire:navigate
                       class="flex-1 bg-white shadow-sm rounded-2xl p-4 hover:shadow-md transition">
                        <span class="text-xs text-gray-400 font-semibold uppercase tracking-wide">← Previous</span>
                        <span class="block mt-1 text-sm font-semibold text-gray-800 leading-5">{{ $prevLesson->title }}</span>
                    </a>
                @else
                    <span class="flex-1"></span>
                @endif

                @if ($nextLesson)
                    <a href="{{ route('topics.lessons.show', [$topic->slug, $nextLesson->slug]) }}" wire:navigate
                       class="flex-1 text-right bg-white shadow-sm rounded-2xl p-4 hover:shadow-md transition">
                        <span class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Next →</span>
                        <span class="block mt-1 text-sm font-semibold text-gray-800 leading-5">{{ $nextLesson->title }}</span>
                    </a>
                @else
                    <span class="flex-1"></span>
                @endif
            </nav>
        </div>
    </div>
</x-app-layout>

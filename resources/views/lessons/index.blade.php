<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lessons') }} · {{ $topic->name }}
        </h2>
    </x-slot>

    <x-module-tabs :topic="$topic" active="lessons" :lesson-count="$lessons->count()" />

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-sm text-gray-500 mb-5">
                Short, structured explanations of the mechanics this module tests — read before you drill, or after a
                wrong answer in the quiz. Every lesson walks through <span class="font-medium text-gray-700">one idea</span>
                with step-by-step examples and the intermediate states, so you see <em>how</em> a value changes, not just
                the final result.
            </p>

            <div class="space-y-4">
                @forelse ($lessons as $lesson)
                    <a href="{{ route('topics.lessons.show', [$topic->slug, $lesson->slug]) }}" wire:navigate
                       class="block bg-white shadow-sm rounded-2xl p-5 hover:shadow-md transition border border-transparent hover:border-teal-200">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-900 text-[17px] mb-1">{{ $lesson->title }}</h3>
                                <p class="text-sm text-gray-600 leading-6">{{ $lesson->summary }}</p>
                                @if ($lesson->tags)
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($lesson->tags as $tag)
                                            <span class="px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 text-xs font-medium border border-teal-100">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="shrink-0 flex flex-col items-end gap-2">
                                <span class="text-xs text-gray-400 font-medium">
                                    {{ $lesson->minutes ? $lesson->minutes.' min read' : 'lesson' }}
                                    @if ($lesson->sections_count > 0)
                                        · {{ $lesson->sections_count }} {{ $lesson->sections_count === 1 ? 'part' : 'parts' }}
                                    @endif
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide
                                    {{ $lesson->difficulty === 'easy' ? 'bg-emerald-100 text-emerald-700' : ($lesson->difficulty === 'hard' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ $lesson->difficulty }}
                                </span>
                                <span class="text-teal-600 font-semibold text-sm mt-1">Read →</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="bg-white shadow-sm rounded-2xl p-10 text-center">
                        <div class="text-5xl mb-3">📭</div>
                        <h3 class="font-semibold text-gray-900 mb-1">No lessons in this module yet</h3>
                        <p class="text-sm text-gray-500">Lessons are being written module by module.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-6 flex justify-center">
                <a href="{{ route('quiz.topic', $topic->slug) }}" wire:navigate
                   class="px-5 py-2.5 bg-gray-900 text-white rounded-lg font-semibold hover:bg-gray-700">
                    Or jump straight into the quiz →
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

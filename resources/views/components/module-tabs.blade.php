@props(['topic', 'active' => 'lessons', 'lessonCount' => 0])

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('topics') }}" wire:navigate class="text-sm text-gray-400 hover:text-gray-600 font-medium">← All modules</a>
            <div class="flex items-center gap-2.5 mt-1">
                <span class="text-3xl">{{ $topic->icon }}</span>
                <h1 class="text-2xl font-bold text-gray-900">{{ $topic->name }}</h1>
            </div>
        </div>

        <nav class="flex items-center gap-1.5 bg-white rounded-xl p-1 shadow-sm border border-gray-200">
            <a href="{{ route('quiz.topic', $topic->slug) }}" wire:navigate
               class="px-4 py-1.5 rounded-lg text-sm font-semibold transition {{ $active === 'quiz' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                Quiz
            </a>
            <a href="{{ route('topics.lessons.index', $topic->slug) }}" wire:navigate
               class="px-4 py-1.5 rounded-lg text-sm font-semibold transition {{ $active === 'lessons' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                📖 Lessons
                @if ($lessonCount > 0)
                    <span class="ms-1 text-xs {{ $active === 'lessons' ? 'text-teal-100' : 'text-gray-400' }}">{{ $lessonCount }}</span>
                @endif
            </a>
        </nav>
    </div>
</div>

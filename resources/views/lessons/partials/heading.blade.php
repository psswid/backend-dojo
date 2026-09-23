{{-- Lesson section: heading with anchor for the table of contents --}}
<h2 id="{{ $section->anchor() }}" class="flex items-center gap-2.5 text-lg sm:text-xl font-bold text-gray-900 pt-4 -mt-2 scroll-mt-24">
    <span class="shrink-0 w-1.5 h-6 rounded-full bg-teal-500"></span>
    {{ $section->title }}
</h2>

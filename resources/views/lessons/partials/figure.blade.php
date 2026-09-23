{{-- Lesson section: diagram (author-authored HTML using .ld-* classes from app.css) --}}
<figure class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 overflow-x-auto">
    @if (! empty($payload['title']))
        <figcaption class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
            <span>📊</span>{{ $payload['title'] }}
        </figcaption>
    @endif
    <div class="lesson-diagram">{!! $payload['html'] ?? '' !!}</div>
    @if (! empty($payload['caption']))
        <figcaption class="mt-3 text-xs text-gray-500 leading-5 flex gap-1.5">
            <span>ℹ️</span><span>{!! \App\Support\LessonText::inline($payload['caption']) !!}</span>
        </figcaption>
    @endif
</figure>

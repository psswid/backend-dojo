{{-- Lesson section: code block --}}
@php
    $langLabel = [
        'php' => 'PHP', 'sql' => 'SQL', 'bash' => 'Shell', 'text' => 'Text', 'plain' => 'Plain',
    ];
@endphp
<figure class="mt-1">
    @if (! empty($payload['title']) || ! empty($payload['lang']))
        <figcaption class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500">{{ $payload['title'] ?? '' }}</span>
            @if (! empty($payload['lang']) && isset($langLabel[$payload['lang']]))
                <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-gray-100 text-gray-400">{{ $langLabel[$payload['lang']] }}</span>
            @endif
        </figcaption>
    @endif
    <pre class="rounded-xl bg-slate-900 text-slate-100 p-4 overflow-x-auto text-[13px] leading-6 font-mono whitespace-pre"><code>{{ $payload['code'] ?? '' }}</code></pre>
    @if (! empty($payload['caption']))
        <figcaption class="mt-2 text-xs text-gray-400 leading-5">{!! \App\Support\LessonText::inline($payload['caption']) !!}</figcaption>
    @endif
</figure>

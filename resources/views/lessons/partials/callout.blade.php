{{-- Lesson section: colored callout --}}
@php
    $tones = [
        'tip'     => ['💡', 'border-emerald-300 bg-emerald-50', 'text-emerald-900'],
        'watch'   => ['⚠️', 'border-amber-300 bg-amber-50', 'text-amber-900'],
        'why'     => ['🧠', 'border-indigo-300 bg-indigo-50', 'text-indigo-900'],
        'warning' => ['🚨', 'border-red-300 bg-red-50', 'text-red-900'],
        'danger'  => ['🚨', 'border-red-300 bg-red-50', 'text-red-900'],
    ];
    $t = $tones[$payload['tone'] ?? 'tip'] ?? $tones['tip'];
@endphp
<div class="rounded-xl border-2 {{ $t[1] }} px-4 py-3 flex gap-3">
    <span class="text-lg leading-6 shrink-0">{{ $t[0] }}</span>
    <div class="min-w-0">
        @if (! empty($payload['title']))
            <p class="font-bold {{ $t[2] }} mb-0.5 text-[15px]">{{ $payload['title'] }}</p>
        @endif
        <div class="text-[14px] leading-6 {{ $t[2] }} opacity-90">
            {!! \App\Support\LessonText::prose($payload['text'] ?? '') !!}
        </div>
    </div>
</div>

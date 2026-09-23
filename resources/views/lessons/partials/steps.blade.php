{{-- Lesson section: example split into numbered, digestible steps --}}
@php
    $steps = $payload['steps'] ?? [];
@endphp
<div>
    @if (! empty($payload['title']))
        <h3 class="text-base font-bold text-gray-900 mb-1">{{ $payload['title'] }}</h3>
    @endif
    @if (! empty($payload['intro']))
        <p class="text-[15px] text-gray-600 leading-7 mb-4">{!! \App\Support\LessonText::inline($payload['intro']) !!}</p>
    @endif

    <ol class="space-y-4">
        @foreach ($steps as $i => $step)
            <li class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5">
                <div class="flex items-center gap-3 mb-1">
                    <span class="shrink-0 w-7 h-7 rounded-full bg-teal-600 text-white flex items-center justify-center text-sm font-bold">{{ $i + 1 }}</span>
                    @if (! empty($step['title']))
                        <h4 class="font-semibold text-gray-900 text-[15px] leading-6">{{ $step['title'] }}</h4>
                    @endif
                </div>

                @if (! empty($step['text']))
                    <div class="text-[15px] leading-7 text-gray-700">{{-- prose? plain inline --}}
                        {!! \App\Support\LessonText::prose($step['text']) !!}
                    </div>
                @endif

                @if (! empty($step['code']))
                    <pre class="mt-3 rounded-lg bg-slate-900 text-slate-100 p-3.5 overflow-x-auto text-[13px] leading-6 font-mono whitespace-pre"><code>{{ $step['code'] }}</code></pre>
                @endif

                @if (! empty($step['table']))
                    <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-white">
                                    @foreach ($step['table']['headers'] ?? [] as $header)
                                        <th class="px-3 py-1.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 border-b border-gray-200">{!! \App\Support\LessonText::inline($header) !!}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($step['table']['rows'] ?? [] as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td class="px-3 py-1.5 font-mono text-[13px] text-gray-700 whitespace-nowrap">{!! \App\Support\LessonText::inline((string) $cell) !!}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if (array_key_exists('state', $step))
                    <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                        <span class="font-semibold text-gray-500">After this step →</span>
                        <code class="lesson-code">{{ $step['state'] }}</code>
                    </p>
                @endif
            </li>
        @endforeach
    </ol>
</div>

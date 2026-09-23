{{-- Lesson section: state-transition table --}}
@php
    $headers = $payload['headers'] ?? [];
    $rows = $payload['rows'] ?? [];
    $monoCols = array_map('intval', $payload['mono'] ?? []);
    $emCols = array_map('intval', $payload['em'] ?? []);
@endphp
<figure class="overflow-hidden rounded-xl border border-gray-200">
    @if (! empty($payload['title']))
        <figcaption class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
            {{ $payload['title'] }}
        </figcaption>
    @endif
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            @if ($headers !== [])
                <thead>
                    <tr class="bg-gray-50/70">
                        @foreach ($headers as $i => $header)
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 border-b border-gray-200 whitespace-nowrap">{!! \App\Support\LessonText::inline($header) !!}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-gray-100">
                @foreach ($rows as $row)
                    <tr class="hover:bg-teal-50/40">
                        @foreach ($row as $colIdx => $cell)
                            @php
                                $cls = 'px-4 py-2 align-top text-gray-700 leading-6';
                                if (in_array($colIdx, $monoCols, true)) { $cls .= ' font-mono text-[13px] whitespace-nowrap'; }
                                if (in_array($colIdx, $emCols, true)) { $cls .= ' font-semibold text-gray-900'; }
                            @endphp
                            <td class="{{ $cls }}">{!! \App\Support\LessonText::inline((string) $cell) !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if (! empty($payload['note']))
        <p class="px-4 py-2 bg-gray-50 border-t border-gray-200 text-xs text-gray-500 leading-5">{!! \App\Support\LessonText::inline($payload['note']) !!}</p>
    @endif
</figure>

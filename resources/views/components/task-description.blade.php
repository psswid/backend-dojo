@props(['blocks' => []])

@foreach ($blocks as $block)
    @switch($block['type'])
        @case('signature')
            <div class="mb-4 rounded-xl bg-gray-900 px-4 py-3 overflow-x-auto">
                <code class="text-[13px] text-emerald-300 font-mono whitespace-pre">{{ $block['text'] }}</code>
            </div>
            @break

        @case('body')
            <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $block['text'] }}</div>
            @break

        @case('rules')
            <div class="mt-4 rounded-xl bg-gray-50 border border-gray-200 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-2">Rules</p>
                <ul class="space-y-1.5">
                    @foreach ($block['items'] ?? [] as $item)
                        <li class="flex items-start gap-2 text-sm text-gray-700">
                            <span class="mt-0.5 text-amber-500 shrink-0">▸</span>
                            <span>{{ $item['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @break

        @case('examples')
            <div class="mt-4 rounded-xl bg-gray-50 border border-gray-200 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-2">Examples</p>
                <ul class="space-y-2">
                    @foreach ($block['items'] ?? [] as $item)
                        <li class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-sm">
                            <code class="font-mono text-indigo-700 break-all">{{ $item['call'] }}</code>
                            <span class="text-gray-400">→</span>
                            <code class="font-mono text-emerald-700 break-all">{{ $item['output'] }}</code>
                        </li>
                    @endforeach
                </ul>
            </div>
            @break

        @case('hint')
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-600 mb-1">Hint</p>
                <div class="text-sm text-amber-900 leading-relaxed whitespace-pre-line">{{ $block['text'] }}</div>
            </div>
            @break

        @default
            <div class="text-sm text-gray-700 whitespace-pre-line">{{ $block['text'] ?? '' }}</div>
    @endswitch
@endforeach

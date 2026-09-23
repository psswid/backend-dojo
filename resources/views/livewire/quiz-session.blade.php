<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if ($finished)
        <div class="bg-white shadow rounded-2xl p-8 text-center">
            <div class="text-6xl mb-4">{{ $sessionCorrect === $sessionTotal ? '🏆' : '🎯' }}</div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Session complete</h2>
            <p class="text-gray-600 mb-1">
                You answered <span class="font-semibold text-gray-900">{{ $sessionCorrect }} / {{ $sessionTotal }}</span> correctly
                @if ($sessionTotal > 0)
                    ({{ round(100 * $sessionCorrect / $sessionTotal) }}%)
                @endif
            </p>
            <p class="text-sm text-gray-500 mb-6">Every answer was scheduled for spaced repetition — weak ones will return sooner.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="restart" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 font-medium">Practice again</button>
                <a href="{{ route('topics') }}" wire:navigate class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">All modules</a>
                <a href="{{ route('dashboard') }}" wire:navigate class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">Dashboard</a>
            </div>
        </div>
    @elseif (! $question)
        <div class="bg-white shadow rounded-2xl p-10 text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">
                {{ $topicId ? 'No questions in this module yet.' : 'Nothing due right now.' }}
            </h2>
            <p class="text-gray-600 mb-6">
                {{ $topicId ? 'Pick another module to practice.' : 'Complete a module or wait for the next scheduled review.' }}
            </p>
            <a href="{{ route('topics') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 font-medium">Browse modules</a>
        </div>
    @else
        <div class="mb-4 flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                @if ($topicId)
                    <span class="text-2xl">{{ $question->topic->icon }}</span>
                    <a href="{{ route('topics') }}" wire:navigate class="text-gray-500 hover:text-gray-800 font-medium">{{ $title }}</a>
                @else
                    <span class="text-2xl">🔄</span>
                    <span class="text-gray-500 font-medium">{{ $title }}</span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide
                    {{ $question->difficulty === 'easy' ? 'bg-emerald-100 text-emerald-700' : ($question->difficulty === 'hard' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                    {{ $question->difficulty }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide bg-indigo-100 text-indigo-700">
                    @php $typeLabels = ['single' => 'Single choice', 'multi' => 'Multiple choice', 'open' => 'Open answer']; @endphp
                    {{ $typeLabels[$question->type] ?? $question->type }}
                </span>
                <span class="text-gray-400 font-mono">{{ $this->progress }}</span>
            </div>
        </div>

        <div class="w-full h-1.5 bg-gray-200 rounded-full mb-6 overflow-hidden">
            @php $pct = count($questionIds) > 0 ? round(100 * ($index + 1) / count($questionIds)) : 0; @endphp
            <div class="h-full bg-indigo-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>

        @if ($resumed && ! $finished)
            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-sm text-amber-900">
                    <span>↩️</span>
                    <p>
                        Resumed an interrupted session —
                        question <span class="font-semibold">{{ $this->progress }}</span>
                        @if ($sessionTotal > 0)
                            · score <span class="font-semibold">{{ $sessionCorrect }}/{{ $sessionTotal }}</span>
                        @endif
                    </p>
                </div>
                <button wire:click="restart" wire:confirm="Discard this session and start over?"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-amber-800 bg-amber-100 hover:bg-amber-200">
                    Start over
                </button>
            </div>
        @endif

        <div class="bg-white shadow rounded-2xl p-6 sm:p-8">
            {{-- Prompt --}}
            @php
                $promptBlocks = \App\Support\QuizText::promptBlocks($question->prompt);
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
            @endphp

            <div class="mb-6">
                @foreach ($promptBlocks as $block)
                    @if ($block['type'] === 'enum')
                        <ol class="space-y-2.5">
                            @foreach ($block['items'] as $item)
                                <li class="flex gap-3">
                                    <span class="shrink-0 mt-1 w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center text-sm font-bold">{{ $item['k'] }}</span>
                                    <div class="text-lg sm:text-xl text-gray-900 leading-8">{!! $item['html'] !!}</div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 leading-9 whitespace-pre-line">{!! $block['html'] !!}</h1>
                    @endif
                @endforeach
            </div>

            @if ($question->isChoice())
                <div class="space-y-3">
                    @foreach ($question->options as $i => $option)
                        @php
                            $isSelected = in_array($i, $selected, true);
                            $isCorrectOpt = in_array($i, $question->correct, true);
                            $letter = $letters[$i] ?? ($i + 1);
                            $isCode = \App\Support\QuizText::isCodeLikeOption($option);

                            $base = 'w-full text-left px-4 py-3 rounded-xl border-2 transition flex items-start gap-3';
                            if ($revealed && $isCorrectOpt) {
                                $state = 'border-emerald-500 bg-emerald-50';
                            } elseif ($revealed && $isSelected && ! $isCorrectOpt) {
                                $state = 'border-red-500 bg-red-50';
                            } elseif ($isSelected) {
                                $state = 'border-indigo-500 bg-indigo-50';
                            } else {
                                $state = 'border-gray-200 bg-white hover:border-indigo-300';
                            }

                            $letterBox = 'shrink-0 w-7 h-7 rounded-lg border-2 flex items-center justify-center text-sm font-bold';
                            if ($revealed && $isCorrectOpt) {
                                $letterBox .= ' border-emerald-500 text-emerald-600';
                            } elseif ($revealed && $isSelected && ! $isCorrectOpt) {
                                $letterBox .= ' border-red-500 text-red-600';
                            } elseif ($isSelected) {
                                $letterBox .= ' border-indigo-500 bg-indigo-500 text-white';
                            } else {
                                $letterBox .= ' border-gray-300 text-gray-500';
                            }
                        @endphp
                        <button wire:click="{{ $question->type === 'multi' ? 'toggleOption('.$i.')' : 'selectOption('.$i.')' }}"
                                @disabled($revealed)
                                class="{{ $base }} {{ $state }} {{ $revealed ? 'cursor-default' : 'cursor-pointer' }}">
                            <span class="{{ $letterBox }}">
                                {{ $revealed && $isCorrectOpt ? '✓' : ($revealed && $isSelected && ! $isCorrectOpt ? '✗' : $letter) }}
                            </span>
                            <span class="flex-1 min-w-0 {{ $isCode ? 'font-mono text-[15px] leading-relaxed pt-0.5' : 'text-gray-800 leading-7 pt-0.5' }} break-words">{{ $option }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button wire:click="answer"
                            @disabled($revealed || empty($selected))
                            class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        {{ $revealed ? 'Answered' : 'Check answer' }}
                    </button>
                    @if ($question->type === 'multi' && ! $revealed)
                        <span class="ml-3 text-sm text-gray-500">Select all that apply.</span>
                    @endif
                </div>
            @else
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your answer (optional — think it through first)</label>
                    <textarea wire:model.debounce.1000ms="openAnswer" rows="5" @disabled($revealed)
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                        placeholder="Sketch your approach or answer here…"></textarea>
                </div>
                <div class="mt-4">
                    @if (! $revealed)
                        <button wire:click="reveal" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500">Reveal model answer</button>
                    @elseif ($openQuality === null)
                        <div class="rounded-xl bg-gray-50 p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-3">How well did you know it?</p>
                            <div class="flex flex-wrap gap-2">
                                <button wire:click="grade(1)" class="px-4 py-2 rounded-lg bg-red-100 text-red-700 font-semibold hover:bg-red-200">Again (1)</button>
                                <button wire:click="grade(3)" class="px-4 py-2 rounded-lg bg-amber-100 text-amber-700 font-semibold hover:bg-amber-200">Hard (3)</button>
                                <button wire:click="grade(5)" class="px-4 py-2 rounded-lg bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200">Good (5)</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if ($revealed)
                @php
                    $correctLetters = array_map(
                        fn ($idx) => $letters[$idx] ?? ($idx + 1),
                        array_map('intval', $question->correct ?? [])
                    );
                    $isWrong = $correct === false;
                @endphp
                <div class="mt-6 rounded-2xl overflow-hidden border-2 {{ $isWrong ? 'border-red-200' : 'border-emerald-200' }}">
                    {{-- Verdict header --}}
                    <div class="px-5 py-3 flex items-center justify-between gap-3 {{ $isWrong ? 'bg-red-50' : 'bg-emerald-50' }}">
                        <p class="font-bold {{ $isWrong ? 'text-red-800' : 'text-emerald-800' }}">
                            {{ $isWrong ? '✗ Not quite' : ($correct === true ? '✓ Correct' : 'Model answer') }}
                        </p>
                        @if ($question->isChoice() && ! empty($correctLetters))
                            <p class="text-sm font-semibold {{ $isWrong ? 'text-red-700' : 'text-emerald-700' }}">
                                Correct answer: {{ implode(', ', $correctLetters) }}
                            </p>
                        @endif
                    </div>

                    {{-- Explanation body --}}
                    <div class="bg-white px-5 py-4 sm:px-6 sm:py-5">
                        @php $explBlocks = \App\Support\QuizText::explanationBlocks($question->explanation); @endphp
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Explanation</p>
                        <div class="max-w-none text-gray-800 space-y-4">
                            @foreach ($explBlocks as $block)
                                @if ($block['type'] === 'code')
                                    <pre class="rounded-xl bg-slate-900 text-slate-100 p-4 overflow-x-auto text-[13px] leading-6 font-mono whitespace-pre">{!! $block['html'] !!}</pre>
                                @else
                                    <p class="text-[15px] leading-7 whitespace-pre-line">{!! $block['html'] !!}</p>
                                @endif
                            @endforeach
                        </div>

                        @if ($question->resources->isNotEmpty())
                            <div class="mt-5 border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Dig deeper</p>
                                <ul class="space-y-1.5">
                                    @foreach ($question->resources as $resource)
                                        <li>
                                            <a href="{{ $resource->url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline text-sm">
                                                {{ $resource->title }}
                                                <span class="text-gray-400">({{ $resource->type }})</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @php $relatedLessons = \App\Models\Lesson::forQuestion($question); @endphp
                        @if ($relatedLessons->isNotEmpty())
                            <div class="mt-5 border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">
                                    📖 Read the lesson{{ $relatedLessons->count() > 1 ? 's' : '' }} first
                                </p>
                                <ul class="space-y-1.5">
                                    @foreach ($relatedLessons as $lesson)
                                        <li>
                                            <a href="{{ route('topics.lessons.show', [$question->topic->slug, $lesson->slug]) }}"
                                               target="_blank" rel="noopener"
                                               class="text-teal-700 hover:underline text-sm font-medium">
                                                {{ $lesson->title }}
                                                @if ($lesson->minutes)
                                                    <span class="text-gray-400 font-normal">({{ $lesson->minutes }} min)</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="next" class="px-5 py-2.5 bg-gray-900 text-white rounded-lg font-semibold hover:bg-gray-700">
                        {{ $index + 1 >= count($questionIds) ? 'Finish' : 'Next →' }}
                    </button>
                </div>
            @endif
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
            <span>Score this session: <span class="font-semibold text-gray-700">{{ $sessionCorrect }}/{{ $sessionTotal }}</span></span>
            <a href="{{ route('topics') }}" wire:navigate class="hover:text-gray-800">Exit session</a>
        </div>
    @endif
</div>

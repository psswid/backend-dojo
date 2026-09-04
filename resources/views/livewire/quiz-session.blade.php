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
                    {{ $question->type }}
                </span>
                <span class="text-gray-400 font-mono">{{ $this->progress }}</span>
            </div>
        </div>

        <div class="w-full h-1.5 bg-gray-200 rounded-full mb-6 overflow-hidden">
            @php $pct = count($questionIds) > 0 ? round(100 * ($index + 1) / count($questionIds)) : 0; @endphp
            <div class="h-full bg-indigo-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>

        <div class="bg-white shadow rounded-2xl p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 leading-snug mb-6">{{ $question->prompt }}</h1>

            @if ($question->isChoice())
                <div class="space-y-3">
                    @foreach ($question->options as $i => $option)
                        @php
                            $isSelected = in_array($i, $selected, true);
                            $isCorrectOpt = in_array($i, $question->correct, true);
                            $base = 'w-full text-left px-4 py-3 rounded-xl border-2 transition font-medium flex items-start gap-3';
                            if ($revealed && $isCorrectOpt) {
                                $state = 'border-emerald-500 bg-emerald-50 text-emerald-900';
                            } elseif ($revealed && $isSelected && ! $isCorrectOpt) {
                                $state = 'border-red-500 bg-red-50 text-red-900';
                            } elseif ($isSelected) {
                                $state = 'border-indigo-500 bg-indigo-50 text-indigo-900';
                            } else {
                                $state = 'border-gray-200 bg-white text-gray-700 hover:border-indigo-300';
                            }
                        @endphp
                        <button wire:click="{{ $question->type === 'multi' ? 'toggleOption('.$i.')' : 'selectOption('.$i.')' }}"
                                @disabled($revealed)
                                class="{{ $base }} {{ $state }} {{ $revealed ? 'cursor-default' : 'cursor-pointer' }}">
                            <span class="shrink-0 w-6 h-6 rounded-lg border-2 flex items-center justify-center text-xs font-bold
                                {{ $revealed && $isCorrectOpt ? 'border-emerald-500 text-emerald-600' : ($isSelected ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-gray-300 text-transparent') }}">
                                {{ $revealed && $isCorrectOpt ? '✓' : ($isSelected ? ($question->type === 'multi' ? '✓' : '•') : '') }}
                            </span>
                            <span>{{ $option }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button wire:click="answer"
                            @disabled($revealed || empty($selected))
                            class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        {{ $revealed ? 'Answered' : 'Check answer' }}
                    </button>
                </div>
            @else
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your answer (optional — think it through first)</label>
                    <textarea wire:model="openAnswer" rows="5" @disabled($revealed)
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
                <div class="mt-6 rounded-xl border {{ ($correct === false ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50') }} p-5">
                    <p class="font-bold mb-2 {{ $correct === false ? 'text-red-800' : 'text-emerald-800' }}">
                        {{ $correct === false ? '✗ Not quite' : ($correct === true ? '✓ Correct' : 'Model answer') }}
                    </p>
                    <div class="prose prose-sm max-w-none text-gray-800 whitespace-pre-line">{{ $question->explanation }}</div>

                    @if ($question->resources->isNotEmpty())
                        <div class="mt-4 border-t border-gray-200 pt-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Dig deeper</p>
                            <ul class="space-y-1">
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

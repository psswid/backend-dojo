<div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
    @if (! $task)
        <div class="bg-white shadow rounded-2xl p-10 text-center">
            <p class="text-gray-500">Task not found.</p>
            <a href="{{ route('tasks.index') }}" wire:navigate class="text-indigo-600 hover:underline">All tasks</a>
        </div>
    @else
        {{-- ================= Header ================= --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ route('tasks.index') }}" wire:navigate class="text-gray-400 hover:text-gray-700">&larr; Tasks</a>
                <span class="text-gray-300">/</span>
                <span class="text-2xl leading-none">{{ $task->topic->icon }}</span>
                <a href="{{ route('tasks.index') }}" wire:navigate class="text-gray-500 hover:text-gray-800 font-medium">{{ $task->topic->name }}</a>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide
                    {{ $task->type === 'scenario' ? 'bg-violet-100 text-violet-700' : ($task->type === 'debug' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                    {{ $task->type }}
                </span>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide
                    {{ $task->difficulty === 'easy' ? 'bg-emerald-100 text-emerald-700' : ($task->difficulty === 'hard' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                    {{ $task->difficulty }}
                </span>
                @if (! empty($task->tags))
                    @foreach (collect($task->tags)->take(5) as $tag)
                        <span class="px-2 py-1 rounded-md bg-gray-100 text-gray-600 text-xs font-mono">{{ $tag }}</span>
                    @endforeach
                @endif
            </div>
        </div>

        @if ($task->type !== 'scenario')
            {{-- ================= Code tasks: snippet / debug ================= --}}
            <div class="lg:grid lg:grid-cols-12 lg:gap-6 items-start">
                {{-- Left: problem statement --}}
                <div class="lg:col-span-5 mb-6 lg:mb-0">
                    <div class="bg-white shadow rounded-2xl p-6 sm:p-7 lg:sticky lg:top-6">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <h1 class="text-lg font-bold text-gray-900 leading-snug">{{ $task->title }}</h1>
                        </div>

                        {{-- type-specific callout --}}
                        @if ($task->type === 'debug')
                            <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                <span class="font-semibold">🐞 Debug task:</span> this code contains a planted bug. Find it, fix it, and make the test suite pass.
                            </div>
                        @else
                            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">
                                <span class="font-semibold">✍️ Snippet task:</span> implement the function below so the test suite passes. Read the spec carefully — edge cases are part of the test.
                            </div>
                        @endif

                        @php($descBlocks = \App\Support\TaskDescription::blocks($task->description))
                        <x-task-description :blocks="$descBlocks" />
                    </div>
                </div>

                {{-- Right: editor + actions + feedback --}}
                <div class="lg:col-span-7">
                    <div class="bg-gray-900 rounded-2xl shadow overflow-hidden mb-3" wire:ignore>
                        <div class="flex items-center justify-between px-4 py-2.5 bg-gray-800/80 border-b border-gray-700">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-yellow-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-green-500/80"></span>
                                <span class="ml-3 text-xs text-gray-400 font-mono">solution.php</span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-gray-500">
                                <span>PHP 8.4</span>
                                <span class="hidden sm:inline">Tab = indent · Ctrl/⌘ + / = comment · Ctrl/⌘ + Enter = run</span>
                            </div>
                        </div>
                        {{-- Livewire-bound source stays hidden; CodeMirror edits push into it --}}
                        <textarea wire:model.live.debounce.5000="code" data-editor-source class="hidden" aria-hidden="true" tabindex="-1">{{ $code }}</textarea>
                        <div data-editor-host data-editor-lang="php" data-editor-run="runTestsButton" style="height: 480px;"></div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <button id="runTestsButton" wire:click="runTests" wire:loading.attr="disabled" wire:target="runTests"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 disabled:opacity-50">
                            <span wire:loading.remove wire:target="runTests">▶ Run tests</span>
                            <span wire:loading wire:target="runTests" class="inline-flex items-center gap-1.5">Running…</span>
                        </button>
                        <button wire:click="submitCode" wire:loading.attr="disabled" wire:target="submitCode"
                            class="px-4 py-2 bg-gray-900 text-white rounded-lg font-semibold hover:bg-gray-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitCode">Submit</span>
                            <span wire:loading wire:target="submitCode">Submitting…</span>
                        </button>
                        <button wire:click="getHint" wire:loading.attr="disabled" wire:target="getHint"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 disabled:opacity-50">
                            <span wire:loading.remove wire:target="getHint">🤖 Hint</span>
                            <span wire:loading wire:target="getHint">Thinking…</span>
                        </button>
                        <button wire:click="revealSolution"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-500 rounded-lg font-semibold hover:bg-gray-50">👁 Reveal solution</button>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-5 text-xs">
                        <span class="text-gray-400 flex items-center gap-1.5">
                            @if ($draftState === 'restored')
                                <span>🔄 Draft restored (saved {{ $draftSavedAt }})</span>
                            @elseif ($draftState === 'saved')
                                <span>💾 Saved {{ $draftSavedAt }}</span>
                            @else
                                <span>⏱ Autosaves 5 s after your last edit</span>
                            @endif
                        </span>
                        <div class="ml-auto flex items-center gap-2">
                            <button wire:click="saveDraft"
                                class="px-2.5 py-1 rounded-md bg-gray-100 border border-gray-200 text-gray-600 font-medium hover:bg-gray-200">💾 Save draft</button>
                            <button wire:click="resetDraft" wire:confirm="Reset this task? Your draft will be deleted and the starter code restored."
                                class="px-2.5 py-1 rounded-md bg-white border border-gray-200 text-gray-400 font-medium hover:text-red-600 hover:border-red-200">Reset</button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @if ($hint)
                            <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-indigo-900 text-sm whitespace-pre-line">
                                <span class="font-semibold">🤖 Hint:</span> {{ $hint }}
                            </div>
                        @endif

                        @if ($output !== null)
                            <div class="rounded-xl border {{ $passed ? 'border-emerald-200' : 'border-red-200' }} overflow-hidden">
                                <div class="px-4 py-2.5 {{ $passed ? 'bg-emerald-600' : 'bg-red-600' }} text-white text-sm font-bold flex items-center gap-2">
                                    {{ $passed ? '✓ All tests passed' : '✗ Tests failed' }}
                                </div>
                                <pre class="px-4 py-3 text-sm font-mono whitespace-pre-wrap break-words {{ $passed ? 'bg-emerald-50 text-emerald-900' : 'bg-red-50 text-red-900' }} max-h-80 overflow-y-auto">{{ $output }}</pre>
                            </div>
                        @endif

                        @if ($submitted && $task->solution)
                            <div class="rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-4 py-2 bg-gray-100 text-gray-600 text-xs font-semibold uppercase tracking-wide flex items-center justify-between">
                                    <span>Reference solution</span>
                                </div>
                                <pre class="px-4 py-3 text-sm font-mono text-gray-800 whitespace-pre overflow-x-auto bg-white">{{ $task->solution }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        @else
            {{-- ================= Scenario tasks: free text + AI review ================= --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-7">
                    <div class="bg-white shadow rounded-2xl p-6 sm:p-7 mb-6">
                        <h1 class="text-lg font-bold text-gray-900 mb-3">{{ $task->title }}</h1>
                        @php($descBlocks = \App\Support\TaskDescription::blocks($task->description))
                        <x-task-description :blocks="$descBlocks" />
                    </div>

                    @if ($task->rubric)
                        <div class="rounded-2xl bg-gray-50 border border-gray-200 p-6 mb-6">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">What the interviewer is looking for</p>
                            <ul class="space-y-2">
                                @foreach ($task->rubric['criteria'] ?? [] as $criterion)
                                    <li class="flex items-start gap-2 text-sm text-gray-700">
                                        <span class="mt-1 text-emerald-500 shrink-0">▸</span>
                                        <span>{{ $criterion }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="mt-4 pt-3 border-t border-gray-200 text-xs text-gray-500">
                                Write your answer, then submit — the local AI mentor reviews it against these criteria and scores it 1–5.
                            </p>
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-5">
                    <div class="bg-white shadow rounded-2xl overflow-hidden">
                        <div class="px-4 py-2.5 bg-gray-100 border-b border-gray-200 text-xs text-gray-500 font-semibold uppercase tracking-wide">
                            Your answer
                        </div>
                        <textarea wire:model.live.debounce.5000="answer" rows="16" spellcheck="false"
                            class="w-full resize-y border-0 focus:ring-0 focus:outline-none text-sm text-gray-800 p-4"
                            placeholder="Write your design/answer here…"></textarea>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <button wire:click="submitScenario" wire:loading.attr="disabled" wire:target="submitScenario"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitScenario">Submit for AI review</span>
                            <span wire:loading wire:target="submitScenario">Evaluating…</span>
                        </button>
                        <button wire:click="revealSolution"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-500 rounded-lg font-semibold hover:bg-gray-50">👁 Reveal model answer</button>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 mb-2 text-xs">
                        <span class="text-gray-400">
                            @if ($draftState === 'restored')
                                <span>🔄 Draft restored (saved {{ $draftSavedAt }})</span>
                            @elseif ($draftState === 'saved')
                                <span>💾 Saved {{ $draftSavedAt }}</span>
                            @else
                                <span>⏱ Autosaves 5 s after your last edit</span>
                            @endif
                        </span>
                        <div class="ml-auto flex items-center gap-2">
                            <button wire:click="saveDraft"
                                class="px-2.5 py-1 rounded-md bg-gray-100 border border-gray-200 text-gray-600 font-medium hover:bg-gray-200">💾 Save draft</button>
                            <button wire:click="resetDraft" wire:confirm="Reset this task? Your answer will be deleted."
                                class="px-2.5 py-1 rounded-md bg-white border border-gray-200 text-gray-400 font-medium hover:text-red-600 hover:border-red-200">Reset</button>
                        </div>
                    </div>

                    <div class="space-y-4 mt-2">
                        @if ($aiFeedback)
                            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="font-bold text-indigo-900">🤖 AI review</span>
                                    @if ($aiScore)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $aiScore >= 4 ? 'bg-emerald-100 text-emerald-700' : ($aiScore >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $aiScore }}/5
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-indigo-900 whitespace-pre-line">{{ $aiFeedback }}</div>
                            </div>
                        @endif

                        @if ($submitted && $task->solution)
                            <div class="rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-4 py-2 bg-gray-100 text-gray-600 text-xs font-semibold uppercase tracking-wide">
                                    Model answer
                                </div>
                                <div class="px-4 py-3 text-sm text-gray-800 whitespace-pre-line bg-white">{{ $task->solution }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8">
    @if (! $task)
        <div class="bg-white shadow rounded-2xl p-10 text-center">
            <p class="text-gray-500">Task not found.</p>
            <a href="{{ route('tasks.index') }}" wire:navigate class="text-indigo-600 hover:underline">All tasks</a>
        </div>
    @else
        <div class="mb-4 flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="text-2xl">{{ $task->topic->icon }}</span>
                <a href="{{ route('tasks.index') }}" wire:navigate class="text-gray-500 hover:text-gray-800 font-medium">{{ $task->topic->name }}</a>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide
                    {{ $task->type === 'scenario' ? 'bg-violet-100 text-violet-700' : ($task->type === 'debug' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                    {{ $task->type }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide
                    {{ $task->difficulty === 'easy' ? 'bg-emerald-100 text-emerald-700' : ($task->difficulty === 'hard' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                    {{ $task->difficulty }}
                </span>
            </div>
        </div>

        <div class="bg-white shadow rounded-2xl p-6 sm:p-8 mb-6">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">{{ $task->title }}</h1>
            <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line">{{ $task->description }}</div>
        </div>

        @if ($task->type !== 'scenario')
            {{-- Code tasks: snippet / debug --}}
            <div class="bg-gray-900 rounded-2xl shadow overflow-hidden mb-4">
                <div class="flex items-center justify-between px-4 py-2 bg-gray-800 border-b border-gray-700">
                    <span class="text-xs text-gray-400 font-mono">solution.php</span>
                    <span class="text-xs text-gray-500">PHP 8.4</span>
                </div>
                <textarea wire:model="code" rows="14" spellcheck="false"
                    class="w-full bg-gray-900 text-gray-100 font-mono text-sm p-4 focus:outline-none resize-y"
                    placeholder="// your code here"></textarea>
            </div>

            <div class="flex flex-wrap items-center gap-2 mb-4">
                <button wire:click="runTests" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500">Run tests</button>
                <button wire:click="submitCode" class="px-4 py-2 bg-gray-900 text-white rounded-lg font-semibold hover:bg-gray-700">Submit</button>
                <button wire:click="getHint" wire:loading.attr="disabled" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50">
                    <span wire:loading.remove wire:target="getHint">🤖 Hint</span>
                    <span wire:loading wire:target="getHint">Thinking…</span>
                </button>
                <button wire:click="revealSolution" class="px-4 py-2 bg-white border border-gray-300 text-gray-500 rounded-lg font-semibold hover:bg-gray-50">Reveal solution</button>
            </div>

            @if ($hint)
                <div class="mb-4 rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-indigo-900 text-sm whitespace-pre-line">
                    <span class="font-semibold">🤖 Hint:</span> {{ $hint }}
                </div>
            @endif

            @if ($output !== null)
                <div class="mb-4 rounded-xl border {{ $passed ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-4">
                    <p class="font-bold mb-2 {{ $passed ? 'text-emerald-800' : 'text-red-800' }}">
                        {{ $passed ? '✓ All tests passed' : '✗ Tests failed' }}
                    </p>
                    <pre class="text-sm font-mono whitespace-pre-wrap {{ $passed ? 'text-emerald-900' : 'text-red-900' }}">{{ $output }}</pre>
                </div>
            @endif

            @if ($submitted && $task->solution)
                <div class="mb-4 rounded-xl bg-gray-50 border border-gray-200 p-4">
                    <p class="font-semibold text-gray-700 mb-2">Reference solution</p>
                    <pre class="text-sm font-mono whitespace-pre-wrap text-gray-800">{{ $task->solution }}</pre>
                </div>
            @endif

        @else
            {{-- Scenario tasks: free text + AI review --}}
            @if ($task->rubric)
                <div class="mb-4 rounded-xl bg-gray-50 border border-gray-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">What the interviewer is looking for</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                        @foreach ($task->rubric['criteria'] ?? [] as $criterion)
                            <li>{{ $criterion }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <textarea wire:model="answer" rows="12"
                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm mb-4"
                placeholder="Write your design/answer here…"></textarea>

            <div class="flex items-center gap-2 mb-4">
                <button wire:click="submitScenario" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 disabled:opacity-50">
                    <span wire:loading.remove wire:target="submitScenario">Submit for AI review</span>
                    <span wire:loading wire:target="submitScenario">Evaluating…</span>
                </button>
                <button wire:click="revealSolution" class="px-4 py-2 bg-white border border-gray-300 text-gray-500 rounded-lg font-semibold hover:bg-gray-50">Reveal model answer</button>
            </div>

            @if ($aiFeedback)
                <div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="font-bold text-indigo-900">AI review</span>
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
                <div class="rounded-xl bg-gray-50 border border-gray-200 p-4">
                    <p class="font-semibold text-gray-700 mb-2">Model answer</p>
                    <div class="text-sm text-gray-800 whitespace-pre-line">{{ $task->solution }}</div>
                </div>
            @endif
        @endif
    @endif
</div>

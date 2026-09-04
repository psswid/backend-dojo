<div class="h-[calc(100vh-4rem)] flex">
    {{-- Sidebar: personas + conversations --}}
    <aside class="hidden md:flex md:flex-col w-72 shrink-0 bg-white border-r border-gray-200">
        <div class="p-4 border-b border-gray-100">
            <button wire:click="newConversation"
                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 disabled:opacity-50"
                wire:loading.attr="disabled" wire:target="newConversation">
                + New chat
            </button>
        </div>

        <div class="p-3 border-b border-gray-100 space-y-1">
            <p class="px-1 text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Personas</p>
            @foreach ($personas as $key => $p)
                <button wire:click="switchPersona('{{ $key }}')"
                    class="w-full flex items-start gap-3 px-3 py-2 rounded-lg text-left transition
                        {{ $persona === $key ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50' }}">
                    <span class="text-xl leading-6">{{ $p['icon'] }}</span>
                    <span>
                        <span class="block text-sm font-semibold {{ $persona === $key ? 'text-indigo-900' : 'text-gray-800' }}">{{ $p['name'] }}</span>
                        <span class="block text-xs text-gray-500 leading-snug">{{ $p['description'] }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        <div class="flex-1 overflow-y-auto p-3">
            <p class="px-1 text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">History</p>
            @forelse ($conversations as $c)
                <button wire:click="selectConversation({{ $c->id }})"
                    class="w-full text-left px-3 py-2 rounded-lg transition mb-1
                        {{ $conversationId === $c->id ? 'bg-gray-100' : 'hover:bg-gray-50' }}">
                    <span class="block text-sm text-gray-800 truncate">{{ $c->title ?? ucfirst($c->persona) }}</span>
                    <span class="block text-xs text-gray-400">{{ $c->messages_count }} msg · {{ $c->created_at->diffForHumans() }}</span>
                </button>
            @empty
                <p class="px-3 text-xs text-gray-400">No conversations yet.</p>
            @endforelse
        </div>
    </aside>

    {{-- Chat area --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex items-center gap-3 flex-wrap">
            <span class="text-2xl">{{ $currentPersona['icon'] ?? '🤖' }}</span>
            <div class="min-w-0">
                <h2 class="font-semibold text-gray-900 truncate">{{ $currentPersona['name'] ?? 'Mentor' }}</h2>
                <p class="text-xs text-gray-500 truncate">{{ $currentPersona['description'] ?? '' }}</p>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $online ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $online ? '● online' : '○ offline' }}
                </span>

                @if ($currentPersona['topic_filter'] ?? false)
                    <select wire:model="topicId" class="text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All modules</option>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->icon }} {{ $topic->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if ($persona === 'interviewer' && $conversation && $messages->count() > 0)
                    <button wire:click="finishInterview" wire:loading.attr="disabled" wire:target="finishInterview"
                        class="px-3 py-1.5 bg-gray-900 text-white rounded-lg text-sm font-semibold hover:bg-gray-700 disabled:opacity-50">
                        Finish &amp; report
                    </button>
                @endif

                @if ($persona === 'gap_analyzer')
                    <button wire:click="runGapAnalysis" wire:loading.attr="disabled" wire:target="runGapAnalysis"
                        class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-500 disabled:opacity-50">
                        📊 Run analysis
                    </button>
                @endif
            </div>
        </div>

        {{-- Messages --}}
        <div x-data="{
            init() {
                const el = this.$el;
                const scroll = () => { el.scrollTop = el.scrollHeight; };
                scroll();
                new MutationObserver(scroll).observe(el, { childList: true, subtree: true, characterData: true });
            }
        }"
        class="flex-1 overflow-y-auto bg-gray-50 px-4 sm:px-6 py-6 space-y-4">

            @if ($messages->isEmpty())
                <div class="max-w-2xl mx-auto mt-10 text-center">
                    <span class="text-5xl">{{ $currentPersona['icon'] ?? '🤖' }}</span>
                    <p class="mt-4 text-lg font-semibold text-gray-800">{{ $currentPersona['name'] ?? 'Mentor' }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $currentPersona['description'] ?? '' }}</p>
                    <p class="mt-4 text-sm text-gray-500 italic">“{{ $currentPersona['starter'] ?? 'Ask me anything.' }}”</p>
                    @if (! $online)
                        <p class="mt-6 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                            The AI cluster is offline. Start it with <code class="font-mono">~/ai-cluster/cluster start mars</code>, then send your message.
                        </p>
                    @endif
                </div>
            @else
                @foreach ($messages as $message)
                    @if ($message->role === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-[80%] bg-indigo-600 text-white rounded-2xl rounded-br-md px-4 py-2 text-sm whitespace-pre-wrap break-words">
                                {{ $message->content }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-[85%] bg-white border border-gray-200 rounded-2xl rounded-bl-md px-4 py-3 text-sm shadow-sm">
                                @if ($message->meta['error'] ?? false)
                                    <div class="text-red-700 whitespace-pre-wrap">{{ $message->content }}</div>
                                @else
                                    <div class="prose prose-sm max-w-none prose-pre:bg-gray-900 prose-pre:text-gray-100 break-words">
                                        {!! Str::markdown($message->content, ['html_input' => 'escape']) !!}
                                    </div>
                                    @if ($message->meta['report'] ?? false)
                                        <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-400">📄 Interview report saved to insights</div>
                                    @elseif ($message->meta['gap_analysis'] ?? false)
                                        <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-400">📊 Gap analysis saved to insights</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif

            {{-- Streaming bubble. ALWAYS present so the wire:stream directive is
                 registered on mount; wire:loading.flex reveals it while a
                 streaming action is in flight (and hides it otherwise). --}}
            <div wire:loading.flex wire:target="send,runGapAnalysis,finishInterview" class="justify-start">
                <div class="max-w-[85%] bg-white border border-indigo-200 rounded-2xl rounded-bl-md px-4 py-3 text-sm shadow-sm">
                    <div wire:stream="assistant-stream" class="whitespace-pre-wrap break-words"></div>
                    <span class="inline-block w-2 h-4 bg-indigo-500 animate-pulse"></span>
                </div>
            </div>
        </div>

        {{-- Error banner --}}
        @if ($error)
            <div class="px-4 sm:px-6 py-2 bg-red-50 border-t border-red-200">
                <p class="text-sm text-red-700">⚠️ {{ $error }}</p>
            </div>
        @endif

        {{-- Input --}}
        <div class="bg-white border-t border-gray-200 px-4 sm:px-6 py-4">
            @if (! $online && $messages->isEmpty())
                <p class="text-xs text-amber-700 mb-2">Cluster offline — replies will fail until it is started.</p>
            @endif
            <form wire:submit.prevent="send" class="flex items-end gap-2">
                <textarea wire:model="draft" rows="2" placeholder="Ask… (Shift+Enter for newline)" @keydown.enter.exact.prevent="$wire.send()"
                    class="flex-1 rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-none"></textarea>
                <button type="submit" wire:loading.attr="disabled" wire:target="send"
                    class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-500 disabled:opacity-50 h-[42px]">
                    <span wire:loading.remove wire:target="send">Send</span>
                    <span wire:loading wire:target="send">…</span>
                </button>
            </form>
        </div>
    </div>
</div>

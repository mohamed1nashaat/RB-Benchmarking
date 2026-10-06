<div
    x-data="{
        open: @entangle('open'),
        streaming: @entangle('isStreaming'),
        accumulatedContent: '',

        init() {
            this.$wire.on('ai-send-message', (payload) => {
                this.accumulatedContent = '';
                this.startStream(payload[0]);
            });
        },

        async startStream(payload) {
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';

            let response;
            try {
                response = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
            } catch (err) {
                this.$wire.updateLastMessage('Connection error: could not reach the AI engine.');
                this.$wire.onStreamComplete();
                this.streaming = false;
                return;
            }

            if (!response.ok) {
                this.$wire.updateLastMessage('Error: ' + response.status + ' ' + response.statusText);
                this.$wire.onStreamComplete();
                this.streaming = false;
                return;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop(); // keep partial line

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const raw = line.slice(6).trim();
                    if (raw === '[DONE]') {
                        this.$wire.onStreamComplete();
                        this.streaming = false;
                        return;
                    }
                    try {
                        const parsed = JSON.parse(raw);
                        if (parsed.chunk) {
                            this.accumulatedContent += parsed.chunk;
                            this.$wire.updateLastMessage(this.accumulatedContent);
                        }
                    } catch (_) {}
                }
            }

            this.$wire.onStreamComplete();
            this.streaming = false;
        }
    }"
    class="fixed bottom-6 end-6 z-50 flex flex-col items-end gap-3"
>
    {{-- Chat Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        x-cloak
        class="w-80 sm:w-96 rounded-2xl shadow-2xl overflow-hidden flex flex-col"
        style="height: 480px; background: #111318; border: 1px solid rgba(255,255,255,0.08);"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b" style="border-color: rgba(255,255,255,0.08); background: #0d0f16;">
            <div class="flex items-center gap-2.5">
                <div class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgba(239,100,37,0.2);">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#EF6425">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-white leading-none">AI Analyst</p>
                    <p class="text-[10px] mt-0.5" style="color: rgba(255,255,255,0.35);">Powered by Ollama</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    wire:click="clearHistory"
                    title="Clear conversation"
                    class="h-6 w-6 rounded flex items-center justify-center transition-colors"
                    style="color: rgba(255,255,255,0.35);"
                    onmouseover="this.style.color='rgba(255,255,255,0.7)'"
                    onmouseout="this.style.color='rgba(255,255,255,0.35)'"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </button>
                <button @click="open = false" class="h-6 w-6 rounded flex items-center justify-center" style="color: rgba(255,255,255,0.35);" onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div
            class="flex-1 overflow-y-auto p-4 space-y-3"
            style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;"
            x-ref="msgContainer"
            x-effect="streaming; $nextTick(() => { $refs.msgContainer.scrollTop = $refs.msgContainer.scrollHeight })"
        >
            @if(empty($messages))
                <div class="flex flex-col items-center justify-center h-full gap-3 text-center px-4">
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgba(239,100,37,0.15);">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#EF6425">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-white">Ask your AI Analyst</p>
                        <p class="text-[11px] mt-1" style="color: rgba(255,255,255,0.4);">Try: "Why is my CPL high?" or "Build a Q4 media plan for SAR 200K"</p>
                    </div>
                </div>
            @endif

            @foreach($messages as $i => $message)
                @if($message['role'] === 'user')
                    <div class="flex justify-end">
                        <div class="max-w-[85%] px-3 py-2 rounded-2xl rounded-tr-sm text-xs leading-relaxed" style="background: #EF6425; color: white;">
                            {{ $message['content'] }}
                        </div>
                    </div>
                @else
                    <div class="flex justify-start" wire:ignore.self>
                        <div class="max-w-[90%] px-3 py-2 rounded-2xl rounded-tl-sm text-xs leading-relaxed" style="background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.85);">
                            @if($message['content'] === '' && ($message['streaming'] ?? false))
                                <span class="inline-flex gap-1 items-center" style="color: rgba(255,255,255,0.4);">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full animate-bounce" style="background:currentColor;animation-delay:0ms"></span>
                                    <span class="inline-block h-1.5 w-1.5 rounded-full animate-bounce" style="background:currentColor;animation-delay:150ms"></span>
                                    <span class="inline-block h-1.5 w-1.5 rounded-full animate-bounce" style="background:currentColor;animation-delay:300ms"></span>
                                </span>
                            @else
                                {!! nl2br(e($message['content'])) !!}
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Input --}}
        <div class="p-3 border-t" style="border-color: rgba(255,255,255,0.08);">
            <form wire:submit="sendMessage" class="flex items-end gap-2">
                <textarea
                    wire:model="input"
                    rows="1"
                    placeholder="Ask about your campaigns…"
                    :disabled="streaming"
                    class="flex-1 resize-none rounded-xl px-3 py-2 text-xs leading-relaxed focus:outline-none focus:ring-1"
                    style="background: rgba(255,255,255,0.06); color: white; border: 1px solid rgba(255,255,255,0.1); max-height: 96px; scrollbar-width: thin; --tw-ring-color: rgba(239,100,37,0.5);"
                    placeholder="Ask about your campaigns…"
                    x-on:keydown.enter.prevent.stop="if (!$event.shiftKey && !streaming) { $wire.sendMessage() }"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 96) + 'px'"
                ></textarea>
                <button
                    type="submit"
                    :disabled="streaming"
                    class="h-8 w-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-opacity"
                    style="background: #EF6425;"
                    :style="streaming ? 'opacity:0.5;cursor:not-allowed' : 'opacity:1;cursor:pointer'"
                >
                    <svg x-show="!streaming" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.269 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    <svg x-show="streaming" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="white" stroke-width="4"></circle>
                        <path class="opacity-75" fill="white" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Floating Toggle Button --}}
    <button
        @click="open = !open"
        class="h-13 w-13 rounded-full shadow-lg flex items-center justify-center transition-all duration-200"
        style="width:52px;height:52px;background:#EF6425;box-shadow:0 4px 24px rgba(239,100,37,0.45);"
        onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'"
        title="AI Analyst"
    >
        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
        </svg>
        <svg x-show="open" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="white">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>
</div>

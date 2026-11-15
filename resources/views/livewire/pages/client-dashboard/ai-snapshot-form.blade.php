    {{-- ── Semrush & Clarity Manual Entry Modal ─────────────────────────── --}}
    @if($aiSnapshotFormOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.6);">
        <div class="card w-full max-w-sm p-6" x-data>
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-semibold text-white">Semrush &amp; Clarity Data</h3>
                <button wire:click="$set('aiSnapshotFormOpen', false)" class="text-gray-500 hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1">Report Month</label>
                    <input wire:model="aiSnapshotMonth" type="month" class="input-field" />
                </div>
                <div class="pt-1 pb-1">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-3">Semrush AI Visibility</p>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Score (0–100)</label>
                            <input wire:model="aiSnapshotSemrushScore" type="number" min="0" max="100" placeholder="0" class="input-field" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Topics</label>
                            <input wire:model="aiSnapshotSemrushTopics" type="number" min="0" placeholder="0" class="input-field" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Prompts</label>
                            <input wire:model="aiSnapshotSemrushPrompts" type="number" min="0" placeholder="0" class="input-field" />
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-600 mt-1.5">From Semrush AI Toolkit → AI Visibility score.</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-3">Microsoft Clarity</p>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">AI Citations</label>
                        <input wire:model="aiSnapshotClarityCitations" type="number" min="0" placeholder="0" class="input-field" />
                    </div>
                    <p class="text-[11px] text-gray-600 mt-1.5">From Clarity Dashboard → AI-referred sessions / citations.</p>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-3">
                <button wire:click="saveAiSnapshot" wire:loading.attr="disabled"
                    class="btn-primary flex-1 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveAiSnapshot">Save</span>
                    <span wire:loading wire:target="saveAiSnapshot">Saving…</span>
                </button>
                <button wire:click="$set('aiSnapshotFormOpen', false)" class="btn-ghost">Cancel</button>
            </div>
        </div>
    </div>
    @endif

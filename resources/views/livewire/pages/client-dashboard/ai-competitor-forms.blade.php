        {{-- Add / Edit Competitor modal --}}
        @if($competitorFormOpen)
        <div class="card p-5 mb-4 !border-brand-500/30">
            <h4 class="text-sm font-semibold text-white mb-3">{{ $editingCompetitorId ? 'Edit Competitor' : 'Add Competitor' }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Name <span class="text-red-400">*</span></label>
                    <input wire:model="competitorName" type="text" placeholder="e.g. Sidr Honey"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Website</label>
                    <input wire:model="competitorWebsite" type="text" placeholder="e.g. sidrhoney.com"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[11px] text-gray-500 mb-1">Other names</label>
                    <input wire:model="competitorAliases" type="text" placeholder="e.g. Arabic name, comma separated"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Industry</label>
                    <input wire:model="competitorIndustry" type="text" placeholder="e.g. e-commerce / honey"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Notes</label>
                    <input wire:model="competitorNotes" type="text" placeholder="Optional"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button wire:click="saveCompetitor" class="px-4 py-1.5 rounded-lg text-xs font-medium text-white" style="background: var(--brand-500, #ef4444);">
                    {{ $editingCompetitorId ? 'Update' : 'Save' }}
                </button>
                <button wire:click="$set('competitorFormOpen', false)" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-white transition-colors">Cancel</button>
            </div>
        </div>
        @endif

        {{-- Competitor Visibility Entry modal --}}
        @if($competitorVisFormOpen)
        <div class="card p-5 mb-4">
            <h4 class="text-sm font-semibold text-white mb-3">Log Metrics</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Month <span class="text-red-400">*</span></label>
                    <input wire:model="competitorVisMonth" type="month"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">AI Impressions (GSC)</label>
                    <input wire:model.number="competitorVisAiImpressions" type="number" placeholder="e.g. 45000"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">AI Sessions (GA4)</label>
                    <input wire:model.number="competitorVisAiSessions" type="number" placeholder="e.g. 320"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Semrush AI Score</label>
                    <input wire:model.number="competitorVisSemrushScore" type="number" min="0" max="100" placeholder="0–100"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Semrush Topics #</label>
                    <input wire:model.number="competitorVisSemrushTopics" type="number" placeholder="e.g. 55"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Copilot Citations</label>
                    <input wire:model.number="competitorVisClarityCitations" type="number" placeholder="e.g. 120"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button wire:click="saveCompetitorVisibility" class="px-4 py-1.5 rounded-lg text-xs font-medium text-white" style="background: var(--brand-500, #ef4444);">Save Metrics</button>
                <button wire:click="$set('competitorVisFormOpen', false)" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-white transition-colors">Cancel</button>
            </div>
        </div>
        @endif

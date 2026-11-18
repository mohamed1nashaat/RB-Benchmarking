{{-- AI prompt tracking: brand spellings, assistants and the weekly prompt list --}}
@php $at = $aiTracking ?? null; @endphp
@if($at)
<div class="card p-5" data-testid="ai-tracking-settings">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="max-w-2xl">
            <h3 class="text-base font-semibold tracking-tight text-white">AI prompt tracking</h3>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Every Monday at 05:00 each active prompt is asked to the assistants below with live web search, the way a shopper would ask. Each answer is checked for the brand and the competitors from the Competitors tab: who is named, in what order, and whose site is cited. Up to {{ $at['limit'] }} active prompts per client.@if($at['lastRun']) Last run {{ $at['lastRun'] }}.@endif</p>
        </div>
        <button type="button" wire:click="seedAiPrompts" class="btn-secondary"><x-heroicon-o-arrow-down-tray class="w-4 h-4" />Add prompts from Semrush</button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 max-w-3xl">
        <label class="block">
            <span class="micro-label">Brand website</span>
            <input type="text" wire:model="aiTrackDomain" placeholder="example.com" class="input-field mt-1.5">
        </label>
        <label class="block">
            <span class="micro-label">Other spellings of the brand (comma separated)</span>
            <input type="text" wire:model="aiTrackAliases" placeholder="e.g. Arabic name, short name" class="input-field mt-1.5" dir="auto">
        </label>
    </div>
    <div class="mt-4">
        <span class="micro-label">Assistants</span>
        <div style="display:flex;flex-wrap:wrap;gap:10px 22px;margin-top:6px">
            @foreach($at['engines'] as $k => $e)
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--ai-text)">
                    <input type="checkbox" wire:model="aiTrackEngines" value="{{ $k }}">
                    {{ $e['label'] }}
                    <span style="font-size:12px;color:{{ $e['key'] ? 'var(--air-good)' : 'var(--ai-muted)' }}">{{ $e['key'] ? 'key set · '.$e['model'] : 'no API key yet' }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;margin-top:14px">
        <button type="button" wire:click="saveAiTracking" class="btn-primary">Save tracking settings</button>
        @if($aiTrackSaved)<span class="text-xs text-emerald-400">{{ $aiTrackSaved }}</span>@endif
    </div>

    <form wire:submit="addAiPrompt" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--ai-border)">
        <label class="block" style="flex:1 1 320px">
            <span class="micro-label">Add a prompt</span>
            <input type="text" wire:model="aiNewPrompt" placeholder="What is the best Sidr honey to buy in Saudi Arabia?" class="input-field mt-1.5" dir="auto">
        </label>
        <label class="block" style="width:110px">
            <span class="micro-label">Country</span>
            <input type="text" wire:model="aiNewPromptCountry" maxlength="2" placeholder="SA" class="input-field mt-1.5">
        </label>
        <button type="submit" class="btn-secondary">Add</button>
    </form>
    @error('aiNewPrompt')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
    @error('aiNewPromptCountry')<p class="text-xs text-red-400 mt-1">Use a 2-letter country code.</p>@enderror

    @php $activeCount = $at['prompts']->where('active', true)->count(); @endphp
    <p class="text-xs text-gray-500" style="margin:14px 0 6px">{{ $activeCount }} active of {{ $at['prompts']->count() }} prompts{{ $activeCount > $at['limit'] ? ' · only the first '.$at['limit'].' by search volume are asked' : '' }}</p>
    @if($at['prompts']->isNotEmpty())
        <div style="max-height:420px;overflow:auto;border:1px solid var(--ai-border);border-radius:8px">
            <table class="data-table">
                <thead><tr><th>Prompt</th><th>Country</th><th>Volume</th><th></th></tr></thead>
                <tbody>
                @foreach($at['prompts'] as $p)
                    <tr wire:key="aitp-{{ $p->id }}" style="{{ $p->active ? '' : 'opacity:.5' }}">
                        <td class="wrap"><span dir="auto">{{ $p->prompt }}</span><br><span class="air-tag" dir="auto">{{ $p->topic ? $p->topic.' · ' : '' }}{{ $p->source === 'semrush' ? 'from Semrush' : 'added manually' }}{{ $p->active ? '' : ' · paused' }}</span></td>
                        <td>{{ $p->country ? strtoupper($p->country) : '—' }}</td>
                        <td>{{ $p->volume ? number_format($p->volume) : '—' }}</td>
                        <td style="white-space:nowrap;text-align:right">
                            <button type="button" wire:click="toggleAiPrompt({{ $p->id }})" class="btn-secondary" style="padding:4px 10px">{{ $p->active ? 'Pause' : 'Resume' }}</button>
                            <button type="button" wire:click="deleteAiPrompt({{ $p->id }})" wire:confirm="Delete this prompt and its history?" class="btn-secondary" style="padding:4px 10px">Delete</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="air-empty">No prompts yet. Add your own above or copy them from the latest Semrush prompts export.</div>
    @endif
</div>
@endif

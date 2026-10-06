@if($activeTabIndex === 2)
@php
    $clientReports = $this->clientReports;
    $statusColors = [
        'draft'     => 'bg-white/8 text-gray-400',
        'ready'     => 'bg-green-500/15 text-green-400',
        'sent'      => 'bg-blue-500/15 text-blue-400',
        'scheduled' => 'bg-amber-500/15 text-amber-400',
    ];
@endphp

<div class="space-y-6">

    {{-- Header + Create button --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-white">Report Designer</h2>
            <p class="text-sm text-gray-400 mt-0.5">Build, design and share visual reports for {{ $dashboardData['client']['name'] ?? 'this client' }}</p>
        </div>
        <button wire:click="createReportForClient"
                wire:loading.attr="disabled"
                class="btn-primary flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span wire:loading.remove wire:target="createReportForClient">New Report</span>
            <span wire:loading wire:target="createReportForClient">Creating…</span>
        </button>
    </div>

    {{-- How it works --}}
    @if(empty($clientReports))
    <div class="card p-8 text-center">
        <div class="h-16 w-16 rounded-2xl bg-brand-500/10 flex items-center justify-center mx-auto mb-4">
            <svg class="h-8 w-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-white mb-1">No reports yet</h3>
        <p class="text-sm text-gray-400 max-w-sm mx-auto mb-6">Create your first visual report with KPI tiles, benchmark comparisons, charts and AI narrative — then share it with a link.</p>
        <div class="flex items-center justify-center gap-6 mb-6">
            @foreach([['icon'=>'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z','label'=>'KPI Tiles'],['icon'=>'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941','label'=>'Trend Charts'],['icon'=>'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z','label'=>'AI Insights'],['icon'=>'M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z','label'=>'Share Link']] as $feat)
            <div class="flex flex-col items-center gap-1.5">
                <div class="h-10 w-10 rounded-xl bg-white/5 flex items-center justify-center">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feat['icon'] }}"/>
                    </svg>
                </div>
                <span class="text-xs text-gray-500">{{ $feat['label'] }}</span>
            </div>
            @endforeach
        </div>
        <button wire:click="createReportForClient" class="btn-primary mx-auto">
            Create First Report
        </button>
    </div>
    @else

    {{-- Reports list --}}
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <th class="text-start px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Report</th>
                    <th class="text-start px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Period</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Blocks</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($clientReports as $rep)
                @php
                    $blocks = count(($rep->layout['blocks'] ?? []));
                    $sc = $statusColors[$rep->status] ?? 'bg-white/8 text-gray-400';
                @endphp
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-5 py-3.5 font-medium text-white">{{ $rep->name }}</td>
                    <td class="px-4 py-3.5 text-gray-400 text-xs font-mono">
                        {{ $rep->period_start->format('d M') }} – {{ $rep->period_end->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $sc }}">
                            {{ ucfirst($rep->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center text-gray-400 text-sm">
                        {{ $blocks > 0 ? $blocks . ' blocks' : '—' }}
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2 justify-end">
                            <a href="{{ route('report-designer', ['id' => $rep->id]) }}"
                               class="flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-md bg-brand-500/10 text-brand-400 hover:bg-brand-500/20 hover:text-brand-300 transition-colors">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                                </svg>
                                Design
                            </a>
                            @if($rep->share_token)
                            <a href="{{ route('report-share', $rep->share_token) }}" target="_blank"
                               class="text-xs text-gray-500 hover:text-gray-200 transition-colors px-2 py-1.5">
                                View
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endif

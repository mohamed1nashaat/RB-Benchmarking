<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-neutral-900">{{ __('navigation.reports') }}</h1>
            <p class="mt-0.5 text-sm text-neutral-500">AI-generated performance reports for your clients.</p>
        </div>
        <a href="{{ route('report-builder') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-md transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('reports.new_report') }}
        </a>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-neutral-200 border border-neutral-200 rounded-lg overflow-hidden">
        <div class="bg-gray-900 px-5 py-4">
            <div class="text-2xl font-bold tracking-tight font-mono">{{ $totalCount }}</div>
            <div class="text-[11px] font-semibold uppercase tracking-wide text-neutral-400 mt-1">Total Reports</div>
        </div>
        <div class="bg-gray-900 px-5 py-4">
            <div class="text-2xl font-bold tracking-tight font-mono text-green-600">{{ $readyCount }}</div>
            <div class="text-[11px] font-semibold uppercase tracking-wide text-neutral-400 mt-1">Ready</div>
        </div>
        <div class="bg-gray-900 px-5 py-4">
            <div class="text-2xl font-bold tracking-tight font-mono">
                {{ \App\Models\Report::where('tenant_id', auth()->user()->tenant_id)->where('status', 'generating')->count() }}
            </div>
            <div class="text-[11px] font-semibold uppercase tracking-wide text-neutral-400 mt-1">Generating</div>
        </div>
        <div class="bg-gray-900 px-5 py-4">
            <div class="text-2xl font-bold tracking-tight font-mono">
                {{ \App\Models\Report::where('tenant_id', auth()->user()->tenant_id)->whereNotNull('sent_at')->count() }}
            </div>
            <div class="text-[11px] font-semibold uppercase tracking-wide text-neutral-400 mt-1">Sent</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-3 flex-wrap">
        <div class="relative flex-1 min-w-48 max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="{{ __('common.search') }}…"
                   class="w-full pl-9 pr-3 py-2 text-sm border border-neutral-200 rounded-md focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition">
        </div>
        <select wire:model.live="statusFilter"
                class="text-sm border border-neutral-200 rounded-md px-3 py-2 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition bg-gray-900 text-neutral-700">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="generating">Generating</option>
            <option value="ready">Ready</option>
            <option value="failed">Failed</option>
        </select>
    </div>

    {{-- Reports table --}}
    <div class="border border-neutral-200 rounded-lg overflow-hidden bg-gray-900">
        @if($reports->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                <div class="h-12 w-12 rounded-xl bg-brand-50 border border-brand-100 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-neutral-700">No reports yet</p>
                <p class="text-xs text-neutral-400 mt-1">Create your first AI-generated report to get started.</p>
                <a href="{{ route('report-builder') }}"
                   class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-md transition-colors">
                    Create first report
                </a>
            </div>
        @else
            <table class="min-w-full divide-y divide-neutral-100">
                <thead>
                    <tr class="bg-neutral-50">
                        <th class="py-3 px-4 text-left text-[11px] font-semibold uppercase tracking-wide text-neutral-400">Report</th>
                        <th class="py-3 px-4 text-left text-[11px] font-semibold uppercase tracking-wide text-neutral-400 hidden sm:table-cell">Period</th>
                        <th class="py-3 px-4 text-left text-[11px] font-semibold uppercase tracking-wide text-neutral-400 hidden md:table-cell">Account</th>
                        <th class="py-3 px-4 text-left text-[11px] font-semibold uppercase tracking-wide text-neutral-400">Status</th>
                        <th class="py-3 px-4 text-left text-[11px] font-semibold uppercase tracking-wide text-neutral-400 hidden xl:table-cell">Schedule</th>
                        <th class="py-3 px-4 text-left text-[11px] font-semibold uppercase tracking-wide text-neutral-400 hidden lg:table-cell">Created</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($reports as $report)
                        <tr class="hover:bg-neutral-50 transition-colors" wire:key="report-{{ $report->id }}">
                            <td class="py-3 px-4">
                                <div class="text-sm font-medium text-neutral-900">{{ $report->name }}</div>
                                @if($report->platform)
                                    <div class="text-xs text-neutral-400 mt-0.5">{{ ucfirst($report->platform) }}{{ $report->industry ? ' · ' . $report->industry : '' }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 hidden sm:table-cell">
                                <span class="text-xs font-mono text-neutral-500">{{ $report->periodLabel() }}</span>
                            </td>
                            <td class="py-3 px-4 hidden md:table-cell">
                                <span class="text-sm text-neutral-600">{{ $report->adAccount?->account_name ?? '—' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide border rounded {{ $report->statusColor() }}">
                                    @if($report->isGenerating())
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    @endif
                                    {{ $report->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 hidden xl:table-cell">
                                @if($report->isScheduled())
                                    <div>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $report->scheduleLabel() }}
                                        </span>
                                        @if($report->next_run_at)
                                            <p class="text-[11px] text-neutral-400 mt-0.5">Next: {{ $report->next_run_at->format('d M') }}</p>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-neutral-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 hidden lg:table-cell">
                                <span class="text-xs text-neutral-400">{{ $report->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('report-builder', ['id' => $report->id]) }}"
                                       class="text-xs font-medium text-brand-600 hover:text-brand-700 transition-colors">
                                        {{ $report->isReady() ? __('common.view') : __('common.edit') }}
                                    </a>
                                    <button wire:click="deleteReport({{ $report->id }})"
                                            wire:confirm="Delete this report?"
                                            class="text-xs font-medium text-neutral-400 hover:text-red-600 transition-colors">
                                        {{ __('common.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($reports->hasPages())
                <div class="px-4 py-3 border-t border-neutral-100">
                    <x-pagination-wrapper :paginator="$reports" />
                </div>
            @endif
        @endif
    </div>

</div>

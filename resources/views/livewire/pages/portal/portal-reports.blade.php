<div class="max-w-5xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">{{ __('portal.reports.title') }}</h1>
            <p class="mt-1 text-sm text-neutral-500">AI-generated performance reports from your account manager</p>
        </div>
    </div>

    <div class="bg-white border border-neutral-200 rounded-xl overflow-hidden">
        @if($reports->isEmpty())
            <div class="py-14 text-center">
                <svg class="mx-auto h-10 w-10 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <p class="mt-3 text-sm text-neutral-500">No reports available yet.</p>
                <p class="text-xs text-neutral-400 mt-1">Your account manager will share reports here once they're ready.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50">
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Report</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Platform</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Period</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($reports as $report)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-neutral-900">{{ $report->name }}</td>
                        <td class="px-4 py-3 text-neutral-600 capitalize">{{ $report->platform ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-neutral-500">{{ $report->periodLabel() }}</td>
                        <td class="px-4 py-3 text-xs text-neutral-400">{{ $report->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($report->share_token)
                            <a href="{{ route('report-share', $report->share_token) }}" target="_blank"
                               class="inline-flex items-center gap-x-1.5 px-3 py-1.5 text-xs font-medium rounded-md border border-neutral-200 text-neutral-700 hover:bg-neutral-100 transition-colors">
                                View Report
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                </svg>
                            </a>
                            @else
                            <span class="inline-flex items-center px-3 py-1.5 text-xs text-neutral-400">Not shared yet</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

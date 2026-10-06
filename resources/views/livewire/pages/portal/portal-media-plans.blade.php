<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-semibold text-neutral-900">{{ __('portal.media_plans.title') }}</h1>
        <p class="mt-1 text-sm text-neutral-500">Approved media plans from your account manager</p>
    </div>

    <div class="bg-white border border-neutral-200 rounded-xl overflow-hidden">
        @if($plans->isEmpty())
            <div class="py-14 text-center">
                <svg class="mx-auto h-10 w-10 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                </svg>
                <p class="mt-3 text-sm text-neutral-500">No approved media plans yet.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50">
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Plan</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Budget</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Period</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Objective</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Platforms</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($plans as $plan)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-neutral-900">{{ $plan->name }}</td>
                        <td class="px-4 py-3 font-mono text-neutral-700">{{ $plan->formattedBudget() }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-neutral-500">{{ $plan->periodLabel() }}</td>
                        <td class="px-4 py-3 text-neutral-600 capitalize">{{ $plan->objective }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($plan->platforms ?? [] as $p)
                                    <span class="inline-block px-1.5 py-0.5 text-xs rounded border border-neutral-200 bg-neutral-50 text-neutral-600">{{ ucfirst($p) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('media-planner-view', $plan->id) }}"
                               class="inline-flex items-center gap-x-1.5 px-3 py-1.5 text-xs font-medium rounded-md border border-neutral-200 text-neutral-700 hover:bg-neutral-100 transition-colors">
                                View
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

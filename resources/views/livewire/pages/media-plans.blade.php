<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">{{ __('navigation.media_planner') }}</h1>
            <p class="mt-1 text-sm text-neutral-500">{{ __('media_planner.subtitle') }}</p>
        </div>
        <a href="{{ route('media-planner-create') }}"
           class="inline-flex items-center gap-x-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('media_planner.new_plan') }}
        </a>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-x-2 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-gray-900 border border-neutral-200 rounded-xl p-4">
            <p class="text-xs text-neutral-500 font-medium uppercase tracking-wide">{{ __('media_planner.stat_total') }}</p>
            <p class="mt-1 text-2xl font-semibold text-neutral-900 font-mono">{{ $totalCount }}</p>
        </div>
        <div class="bg-gray-900 border border-neutral-200 rounded-xl p-4">
            <p class="text-xs text-green-600 font-medium uppercase tracking-wide">{{ __('media_planner.stat_approved') }}</p>
            <p class="mt-1 text-2xl font-semibold text-neutral-900 font-mono">{{ $approvedCount }}</p>
        </div>
        <div class="bg-gray-900 border border-neutral-200 rounded-xl p-4">
            <p class="text-xs text-amber-600 font-medium uppercase tracking-wide">{{ __('media_planner.stat_draft') }}</p>
            <p class="mt-1 text-2xl font-semibold text-neutral-900 font-mono">{{ $draftCount }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-x-3">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="{{ __('common.search') }}..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-neutral-200 rounded-lg bg-gray-900 text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400">
        </div>
        <select wire:model.live="statusFilter"
                class="px-3 py-2 text-sm border border-neutral-200 rounded-lg bg-gray-900 text-neutral-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400">
            <option value="">{{ __('common.all_statuses') }}</option>
            <option value="draft">Draft</option>
            <option value="approved">Approved</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-gray-900 border border-neutral-200 rounded-xl overflow-hidden">
        @if($plans->isEmpty())
            <div class="py-16 text-center">
                <svg class="mx-auto h-10 w-10 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                </svg>
                <p class="mt-3 text-sm text-neutral-500">{{ __('media_planner.no_plans') }}</p>
                <a href="{{ route('media-planner-create') }}"
                   class="mt-4 inline-flex items-center gap-x-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    {{ __('media_planner.new_plan') }}
                </a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50">
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Name</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Budget</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Period</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Objective</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Platforms</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($plans as $plan)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-neutral-900">
                                <a href="{{ route('media-planner-view', $plan->id) }}" class="hover:text-primary-600 transition-colors">
                                    {{ $plan->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-mono text-neutral-700">
                                {{ number_format($plan->total_budget, 0) }} {{ $plan->currency }}
                            </td>
                            <td class="px-4 py-3 font-mono text-neutral-500 text-xs">
                                {{ $plan->periodLabel() }}
                            </td>
                            <td class="px-4 py-3 text-neutral-600 capitalize">
                                {{ $plan->objective }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($plan->platforms ?? [] as $p)
                                        <span class="inline-block px-1.5 py-0.5 text-xs rounded border border-neutral-200 bg-neutral-50 text-neutral-600">{{ ucfirst($p) }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full border {{ $plan->statusColor() }}">
                                    {{ ucfirst($plan->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-neutral-400">
                                {{ $plan->created_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-x-1">
                                    <a href="{{ route('media-planner-view', $plan->id) }}"
                                       class="p-1.5 rounded-md text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 transition-colors"
                                       title="View">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                    </a>
                                    <button wire:click="deletePlan({{ $plan->id }})"
                                            wire:confirm="{{ __('common.confirm_delete') }}"
                                            class="p-1.5 rounded-md text-neutral-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                            title="Delete">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($plans->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200">
                    {{ $plans->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

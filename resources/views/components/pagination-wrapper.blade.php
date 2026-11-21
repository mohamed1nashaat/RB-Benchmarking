@props([
    'paginator',
    'perPageOptions' => [10, 25, 50, 100],
])

@if($paginator->hasPages())
    <div class="bg-gray-900 px-4 py-3 sm:px-6 border-t border-white/10">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-x-4">
                <div class="text-sm text-gray-200">
                    {{ __('Showing') }} {{ $paginator->firstItem() }} {{ __('to') }} {{ $paginator->lastItem() }} {{ __('of') }} {{ $paginator->total() }} {{ __('results') }}
                </div>
                <div class="flex items-center gap-x-2">
                    <label class="text-sm text-gray-200">{{ __('labels.per_page') }}</label>
                    <select wire:model.live="perPage"
                        class="rounded-md border-white/15 text-sm py-1.5 ps-3 pe-8 focus:border-primary-500 focus:ring-primary-500 transition-colors duration-200">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                {{ $paginator->links() }}
            </div>
        </div>
    </div>
@endif

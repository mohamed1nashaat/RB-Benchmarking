@props([
    'paginator',
    'perPageOptions' => [10, 25, 50, 100],
])

@if($paginator->hasPages())
    <div class="bg-white px-4 py-3 sm:px-6 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-700">
                    {{ __('Showing') }} {{ $paginator->firstItem() }} {{ __('to') }} {{ $paginator->lastItem() }} {{ __('of') }} {{ $paginator->total() }} {{ __('results') }}
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-700">{{ __('labels.per_page') }}</label>
                    <select wire:model.live="perPage"
                        class="rounded-md border-gray-300 text-sm py-1.5 pl-3 pr-8 focus:border-primary-500 focus:ring-primary-500 transition-colors duration-200">
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

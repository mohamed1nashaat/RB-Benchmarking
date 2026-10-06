@props([
    'wireModel',
    'options' => [],
    'placeholder' => '',
    'label' => '',
    'icon' => null,
])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            @if($icon)
                <x-dynamic-component :component="$icon" class="h-4 w-4 inline me-1 text-gray-400" />
            @endif
            {{ $label }}
        </label>
    @endif

    <div class="border border-gray-300 rounded-md bg-white max-h-32 overflow-y-auto p-1.5 space-y-1">
        @foreach($options as $value => $optionLabel)
            <label class="flex items-center gap-x-2 text-sm px-1 py-0.5 rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" value="{{ $value }}" wire:model.live="{{ $wireModel }}"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-gray-700">{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
</div>

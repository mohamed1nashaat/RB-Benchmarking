@props([
    'wireModel',
    'options' => [],
    'placeholder' => '',
    'label' => '',
    'icon' => null,
])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-200 mb-1">
            @if($icon)
                <x-dynamic-component :component="$icon" class="h-4 w-4 inline me-1 text-gray-400" />
            @endif
            {{ $label }}
        </label>
    @endif

    <div class="border border-white/15 rounded-md bg-gray-900 max-h-32 overflow-y-auto p-1.5 space-y-1">
        @foreach($options as $value => $optionLabel)
            <label class="flex items-center gap-x-2 text-sm px-1 py-0.5 rounded hover:bg-gray-950 cursor-pointer">
                <input type="checkbox" value="{{ $value }}" wire:model.live="{{ $wireModel }}"
                    class="rounded border-white/15 text-primary-600 focus:ring-primary-500">
                <span class="text-gray-200">{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
</div>

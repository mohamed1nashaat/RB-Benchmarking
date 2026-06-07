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
                <x-dynamic-component :component="$icon" class="h-4 w-4 inline mr-1 text-gray-400" />
            @endif
            {{ $label }}
        </label>
    @endif

    <div
        x-data="select2MultiComponent({
            wireModel: '{{ $wireModel }}',
            placeholder: '{{ $placeholder }}',
        })"
    >
        <select x-ref="select" multiple class="w-full">
            @foreach($options as $value => $optionLabel)
                <option value="{{ $value }}">{{ $optionLabel }}</option>
            @endforeach
        </select>
    </div>
</div>

@props([
    'wireModel' => null,
    'wireChange' => null,
    'alpineKey' => null,
    'initialValue' => null,
    'options' => [],
    'placeholder' => '',
    'label' => '',
    'allowClear' => true,
    'searchable' => true,
    'uid' => null,
])

<div @if($uid) wire:key="{{ $uid }}" @endif>
    @if($label)
        <label class="block text-sm font-medium text-gray-200 mb-1">{{ $label }}</label>
    @endif

    <div
        x-data="select2Component({
            wireModel: '{{ $wireModel }}',
            wireChange: '{{ $wireChange }}',
            alpineKey: '{{ $alpineKey }}',
            initialValue: '{{ $initialValue }}',
            placeholder: '{{ $placeholder }}',
            allowClear: {{ $allowClear ? 'true' : 'false' }},
            searchable: {{ $searchable ? 'true' : 'false' }},
        })"
    >
        <select x-ref="select" class="w-full">
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach($options as $value => $optionLabel)
                <option value="{{ $value }}">{{ $optionLabel }}</option>
            @endforeach
        </select>
    </div>
</div>

@props(['wireModel', 'options' => [], 'label' => '', 'icon' => null, 'placeholder' => 'Select...'])

<div
    x-data="{
        open: false,
        get selected() {
            return $wire.{{ $wireModel }} || [];
        },
        toggle(value) {
            let current = [...($wire.{{ $wireModel }} || [])];
            let idx = current.indexOf(value);
            if (idx > -1) {
                current.splice(idx, 1);
            } else {
                current.push(value);
            }
            $wire.set('{{ $wireModel }}', current);
        },
        isSelected(value) {
            return (this.selected).includes(value);
        }
    }"
    @click.outside="open = false"
    class="relative"
>
    {{-- Label --}}
    @if($label)
        <label class="block text-sm font-medium text-gray-200 mb-1">
            @if($icon)
                <x-dynamic-component :component="$icon" class="h-4 w-4 inline me-1 text-gray-400" />
            @endif
            {{ $label }}
        </label>
    @endif

    {{-- Trigger Button --}}
    <button
        @click="open = !open"
        type="button"
        class="w-full bg-gray-900 border border-white/15 rounded-md shadow-sm px-3 py-2 text-start text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors hover:border-gray-400"
    >
        <div class="flex items-center justify-between">
            <span x-show="selected.length === 0" class="text-gray-500">{{ $placeholder }}</span>
            <span x-show="selected.length > 0" x-cloak class="flex items-center gap-1.5 truncate">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800" x-text="selected.length + ' {{ __('selected') }}'"></span>
            </span>
            <svg class="h-4 w-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full bg-gray-900 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        x-cloak
    >
        <div class="max-h-60 overflow-auto py-1">
            @foreach($options as $value => $optionLabel)
                <label
                    @click.prevent="toggle('{{ $value }}')"
                    class="flex items-center px-3 py-2 hover:bg-gray-950 cursor-pointer transition-colors"
                >
                    <input
                        type="checkbox"
                        :checked="isSelected('{{ $value }}')"
                        class="h-4 w-4 rounded border-white/15 text-primary-600 focus:ring-primary-500 pointer-events-none"
                        tabindex="-1"
                    />
                    <span class="ms-2 text-sm text-gray-200">{{ $optionLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

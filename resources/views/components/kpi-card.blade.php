@props(['label', 'value', 'change' => null, 'changeLabel' => null, 'icon' => null])

<div class="kpi-card">
    <div class="flex items-center justify-between">
        <div>
            <p class="kpi-label">{{ $label }}</p>
            <p class="kpi-value mt-1">{{ $value }}</p>
            @if($change !== null)
                <p class="kpi-change mt-1 {{ $change >= 0 ? 'positive' : 'negative' }}">
                    {{ $change >= 0 ? '+' : '' }}{{ $change }}%
                    @if($changeLabel)
                        <span class="text-gray-400 ms-1">{{ $changeLabel }}</span>
                    @endif
                </p>
            @endif
        </div>
        @if($icon)
            <div class="flex-shrink-0">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>

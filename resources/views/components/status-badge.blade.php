@props(['status'])

@php
    $classes = match($status) {
        'active', 'connected'                        => 'bg-green-500/15 text-green-400',
        'inactive'                                   => 'bg-white/8 text-gray-400',
        'suspended', 'failed', 'error', 'disconnected' => 'bg-red-500/15 text-red-400',
        'completed', 'verified'                      => 'bg-brand-500/15 text-brand-400',
        'generating', 'pending', 'needs_reauth'      => 'bg-amber-500/15 text-amber-400',
        default                                      => 'bg-white/8 text-gray-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide $classes"]) }}>
    {{ __("status.$status") }}
</span>

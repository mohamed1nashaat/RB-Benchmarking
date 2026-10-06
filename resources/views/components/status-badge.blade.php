@props(['status'])

@php
    $classes = match($status) {
        'active', 'connected' => 'bg-[#EDF3EC] text-[#346538]',
        'inactive' => 'bg-[#F1F1EF] text-[#6B6B66]',
        'suspended', 'failed', 'error', 'disconnected' => 'bg-[#FDEBEC] text-[#9F2F2D]',
        'completed', 'verified' => 'bg-[#E1F3FE] text-[#1F6C9F]',
        'generating', 'pending', 'needs_reauth' => 'bg-[#FBF3DB] text-[#956400]',
        default => 'bg-[#F1F1EF] text-[#6B6B66]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide $classes"]) }}>
    {{ __("status.$status") }}
</span>

@props(['status'])

@php
    $classes = match($status) {
        'active', 'connected' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'suspended' => 'bg-red-100 text-red-800',
        'completed' => 'bg-blue-100 text-blue-800',
        'failed' => 'bg-red-100 text-red-800',
        'generating' => 'bg-yellow-100 text-yellow-800',
        default => 'bg-gray-100 text-gray-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ __("status.$status") }}
</span>

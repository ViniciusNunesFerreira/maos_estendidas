@props([
    'href' => null,
    'icon' => null,
    'variant' => 'default',  // default, danger
])

@php
$variantClasses = [
    'default' => 'text-gray-700 hover:bg-gray-100 hover:text-gray-900',
    'danger' => 'text-red-700 hover:bg-red-50 hover:text-red-900',
];

$classes = $variantClasses[$variant] ?? $variantClasses['default'];
$baseClasses = 'group flex items-center px-4 py-2 text-sm transition-colors';
@endphp

@if($href)
    <a 
        href="{{ $href }}" 
        {{ $attributes->merge(['class' => $baseClasses . ' ' . $classes]) }}
    >
        @if($icon)
            <x-icon :name="$icon" class="mr-3 h-5 w-5 {{ $variant === 'danger' ? 'text-red-400' : 'text-gray-400' }} group-hover:{{ $variant === 'danger' ? 'text-red-500' : 'text-gray-500' }}" />
        @endif
        {{ $slot }}
    </a>
@else
    <button 
        type="button"
        {{ $attributes->merge(['class' => $baseClasses . ' ' . $classes . ' w-full text-left']) }}
    >
        @if($icon)
            <x-icon :name="$icon" class="mr-3 h-5 w-5 {{ $variant === 'danger' ? 'text-red-400' : 'text-gray-400' }} group-hover:{{ $variant === 'danger' ? 'text-red-500' : 'text-gray-500' }}" />
        @endif
        {{ $slot }}
    </button>
@endif
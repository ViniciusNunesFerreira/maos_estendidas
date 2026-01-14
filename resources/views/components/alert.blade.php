{{-- resources/views/components/alert.blade.php --}}
@props([
    'type' => 'info',
    'dismissible' => false,
    'title' => null,
])

@php
$types = [
    'success' => [
        'bg' => 'bg-green-50',
        'border' => 'border-green-200',
        'text' => 'text-green-800',
        'icon' => 'check-circle',
        'iconColor' => 'text-green-400',
    ],
    'error' => [
        'bg' => 'bg-red-50',
        'border' => 'border-red-200',
        'text' => 'text-red-800',
        'icon' => 'x-circle',
        'iconColor' => 'text-red-400',
    ],
    'warning' => [
        'bg' => 'bg-yellow-50',
        'border' => 'border-yellow-200',
        'text' => 'text-yellow-800',
        'icon' => 'exclamation-triangle',
        'iconColor' => 'text-yellow-400',
    ],
    'info' => [
        'bg' => 'bg-blue-50',
        'border' => 'border-blue-200',
        'text' => 'text-blue-800',
        'icon' => 'information-circle',
        'iconColor' => 'text-blue-400',
    ],
];

$config = $types[$type] ?? $types['info'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 {$config['bg']} {$config['border']}"]) }} 
     x-data="{ show: true }" 
     x-show="show"
     x-transition>
    <div class="flex">
        <div class="flex-shrink-0">
            <x-icon :name="$config['icon']" class="h-5 w-5 {{ $config['iconColor'] }}" />
        </div>
        <div class="ml-3 flex-1">
            @if($title)
                <h3 class="text-sm font-medium {{ $config['text'] }}">
                    {{ $title }}
                </h3>
            @endif
            <div class="text-sm {{ $config['text'] }} {{ $title ? 'mt-2' : '' }}">
                {{ $slot }}
            </div>
        </div>
        @if($dismissible)
            <div class="ml-auto pl-3">
                <button @click="show = false" class="-mx-1.5 -my-1.5 rounded-lg p-1.5 {{ $config['text'] }} hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>
        @endif
    </div>
</div>
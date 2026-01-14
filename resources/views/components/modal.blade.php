@props([
    'name',
    'title' => null,
    'maxWidth' => 'md',
])

@php
$maxWidthClass = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
][$maxWidth] ?? 'max-w-md';
@endphp

<div
    x-data="{ 
        show: false,
        name: '{{ $name }}'
    }"
    
    @open-modal.window="if ($event.detail === name || $event.detail[0] === name) show = true"
    @close-modal.window="if ($event.detail === name || $event.detail[0] === name) show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div 
        x-show="show" 
        x-transition.opacity
        class="fixed inset-0 bg-gray-500/75 transition-opacity" 
        
    ></div>

    <div class="flex min-h-screen items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="relative w-full {{ $maxWidthClass }} bg-white rounded-lg shadow-xl overflow-hidden"
            @click.stop
        >
            @if($title)
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            <div class="px-6 py-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
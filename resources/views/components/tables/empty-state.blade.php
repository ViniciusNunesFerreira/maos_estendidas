{{-- resources/views/components/empty-state.blade.php --}}
@props([
    'icon' => 'inbox',
    'title' => 'Nenhum item encontrado',
    'description' => null,
    'action' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    <x-icon :name="$icon" class="mx-auto h-12 w-12 text-gray-300" />
    
    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $title }}</h3>
    
    @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif
    
    @if($action && $actionLabel)
        <div class="mt-6">
            <a href="{{ $action }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <x-icon name="plus" class="-ml-1 mr-2 h-5 w-5" />
                {{ $actionLabel }}
            </a>
        </div>
    @endif
    
    {{ $slot }}
</div>
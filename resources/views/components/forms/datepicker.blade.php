<div class="form-group">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input 
            type="date"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $old() }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            class="block w-full px-4 py-2.5 border rounded-lg shadow-sm transition-colors duration-200 
                   focus:ring-2 focus:ring-primary-500 focus:border-primary-500
                   {{ $hasError() ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300' }}
                   disabled:bg-gray-100 disabled:cursor-not-allowed"
            {{ $attributes->except(['class', 'wire:model']) }}
        >
        
        {{-- Ícone de calendário --}}
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
    </div>

    @if($help && !$hasError())
        <p class="mt-2 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if($hasError())
        @foreach($errors() as $error)
            <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
        @endforeach
    @endif
</div>
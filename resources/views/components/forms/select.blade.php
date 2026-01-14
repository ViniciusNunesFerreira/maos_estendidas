{{-- resources/views/components/forms/select.blade.php --}}
<div {{ $attributes->except(['class', 'wire:model']) }}>
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative">
        <select
            name="{{ $name }}"
            id="{{ $inputId }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{-- Compatibilidade Livewire --}}
            {{ $attributes->only(['wire:model', 'wire:model.defer', 'wire:model.live', 'x-model']) }}
            {{-- Chamando o método da classe para as classes CSS --}}
            class="{{ $getSelectClasses() }}"
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            
            @foreach($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" {{ $value == $optValue ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach

            {{ $slot }}
            
        </select>
        
        {{-- Chamando o método hasError() --}}
        @if($hasError())
            <div class="absolute inset-y-0 right-8 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        @endif
    </div>
    
    @if($help && !$hasError())
        <p class="mt-1.5 text-sm text-gray-500">{{ $help }}</p>
    @endif
    
    @if($hasError())
        {{-- Chamando o método getErrorMessage() --}}
        <p class="mt-1.5 text-sm text-red-600">{{ $getErrorMessage() }}</p>
    @endif
</div>
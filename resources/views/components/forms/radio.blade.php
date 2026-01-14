<div class="flex items-start">
    <div class="flex items-center h-5">
        <input 
            id="{{ $name }}-{{ $value }}"
            name="{{ $name }}"
            type="radio"
            value="{{ $value }}"
            @if($isChecked()) checked @endif
            @if($required) required @endif
            class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500 focus:ring-2 transition-colors
                   {{ $hasError() ? 'border-red-500' : '' }}"
            {{ $attributes->except(['class', 'wire:model']) }}
        >
    </div>
    
    @if($label || $description)
        <div class="ml-3">
            @if($label)
                <label for="{{ $name }}-{{ $value }}" class="block text-sm font-medium text-gray-700 cursor-pointer">
                    {{ $label }}
                    @if($required && !$description)
                        <span class="text-red-500">*</span>
                    @endif
                </label>
            @endif
            
            @if($description)
                <p class="text-sm text-gray-500">
                    {{ $description }}
                    @if($required)
                        <span class="text-red-500">*</span>
                    @endif
                </p>
            @endif
        </div>
    @endif
</div>

@if($hasError())
    @foreach($errors() as $error)
        <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
    @endforeach
@endif
<div {{ $attributes->except(['class', 'wire:model']) }}>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative rounded-lg shadow-sm">
        @if($prefix || $icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                @if($icon)
                    <x-icon :name="$icon" class="h-5 w-5 {{ $hasError() ? 'text-red-400' : 'text-gray-400' }}" />
                @else
                    <span class="text-gray-500 sm:text-sm">{{ $prefix }}</span>
                @endif
            </div>
        @endif
        
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $attributes->whereStartsWith(['wire:', 'x-', 'autocomplete']) }}
            class="{{ $getInputClasses() }}"

            @if($mask === 'cpf') oninput="window.maskCPF(this); this.dispatchEvent(new Event('input'))" @endif
            @if($mask === 'phone') oninput="window.maskPhone(this); this.dispatchEvent(new Event('input'))" @endif
            @if($mask === 'cep') oninput="window.maskCEP(this); this.dispatchEvent(new Event('input'))" @endif
            @if($mask === 'currency') oninput="window.maskCurrency(this)" @endif
        />
        
        @if($suffix)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">{{ $suffix }}</span>
            </div>
        @endif
        
        @if($hasError())
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <x-icon name="exclamation-circle" class="h-5 w-5 text-red-500" />
            </div>
        @endif
    </div>
    
    @if($help && !$hasError())
        <p class="mt-1.5 text-sm text-gray-500">{{ $help }}</p>
    @endif
    
    @if($hasError())
        <p class="mt-1.5 text-sm text-red-600">{{ $getErrorMessage() }}</p>
    @endif
</div>
{{-- resources/views/components/forms/textarea.blade.php --}}
<div {{ $attributes->except(['class', 'wire:model']) }}>
    @if($label)
        {{-- Correção: Usando $id (propriedade da classe) ao invés de $inputId --}}
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative">
        <textarea
            name="{{ $name }}"
            id="{{ $id }}" {{-- Correção: Usando $id --}}
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{-- Mantendo a compatibilidade com Livewire --}}
            {{ $attributes->only(['wire:model', 'wire:model.defer', 'wire:model.live', 'wire:model.blur', 'x-model']) }}
            {{-- Correção: Chamando o método da classe --}}
            class="{{ $getTextareaClasses() }}"
        >{{ $value }}</textarea>
    </div>
    
    {{-- Correção: Chamando o método $hasError() --}}
    @if($help && !$hasError())
        <p class="mt-1.5 text-sm text-gray-500">{{ $help }}</p>
    @endif
    
    {{-- Correção: Chamando os métodos de erro --}}
    @if($hasError())
        <p class="mt-1.5 text-sm text-red-600">{{ $getErrorMessage() }}</p>
    @endif
</div>
{{-- resources/views/components/forms/checkbox.blade.php --}}
@props([
    'label' => null,
    'name',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'help' => null,
])

@php
$inputId = $id ?? $name;
$isChecked = $checked || old($name) == $value;
@endphp

<div {{ $attributes }}>
    <div class="flex items-start">
        <div class="flex items-center h-5">
            <input
                type="checkbox"
                name="{{ $name }}"
                id="{{ $inputId }}"
                value="{{ $value }}"
                {{ $isChecked ? 'checked' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
            />
        </div>
        @if($label)
            <div class="ml-3">
                <label for="{{ $inputId }}" class="text-sm font-medium text-gray-700 {{ $disabled ? 'opacity-50' : 'cursor-pointer' }}">
                    {{ $label }}
                </label>
                @if($help)
                    <p class="text-sm text-gray-500">{{ $help }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
{{-- resources/views/components/forms/toggle.blade.php --}}
@props([
    'label' => null,
    'name',
    'id' => null,
    'checked' => false,
    'disabled' => false,
    'help' => null,
])

@php
$inputId = $id ?? $name;
$isChecked = $checked || old($name);
@endphp

<div {{ $attributes }} x-data="{ enabled: {{ $isChecked ? 'true' : 'false' }} }">
    <div class="flex items-center justify-between">
        @if($label)
            <div class="flex-1">
                <label for="{{ $inputId }}" class="text-sm font-medium text-gray-700 {{ $disabled ? 'opacity-50' : 'cursor-pointer' }}">
                    {{ $label }}
                </label>
                @if($help)
                    <p class="text-sm text-gray-500">{{ $help }}</p>
                @endif
            </div>
        @endif
        
        <button
            type="button"
            role="switch"
            @click="enabled = !enabled"
            :aria-checked="enabled"
            {{ $disabled ? 'disabled' : '' }}
            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
            :class="enabled ? 'bg-blue-600' : 'bg-gray-200'"
        >
            <span
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                :class="enabled ? 'translate-x-5' : 'translate-x-0'"
            ></span>
        </button>
        
        <input
            type="hidden"
            name="{{ $name }}"
            id="{{ $inputId }}"
            :value="enabled ? '1' : '0'"
        />
    </div>
</div>
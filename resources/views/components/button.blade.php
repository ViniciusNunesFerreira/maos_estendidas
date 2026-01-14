{{-- resources/views/components/button.blade.php --}}
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $getClasses()]) }}>
        @if($icon)
            <x-icon :name="$icon" :class="$getIconSize()" />
        @endif
        
        {{ $slot }}
        
        @if($iconRight)
            <x-icon :name="$iconRight" :class="$getIconSize()" />
        @endif
    </a>
@else
    <button 
        type="{{ $type }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => $getClasses()]) }}
    >
        @if($icon)
            <x-icon :name="$icon" :class="$getIconSize()" />
        @endif
        
        {{ $slot }}
        
        @if($iconRight)
            <x-icon :name="$iconRight" :class="$getIconSize()" />
        @endif
    </button>
@endif
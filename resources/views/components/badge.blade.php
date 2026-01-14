<span {{ $attributes->merge(['class' => $getClasses()]) }}>
    @if($dot)
        <span class="flex-shrink-0 w-1.5 h-1.5 rounded-full {{ $getDotColor() }}"></span>
    @endif
    
    {{ $slot }}
    
    @if($removable)
        <button type="button" class="-mr-1 ml-0.5 flex-shrink-0 hover:opacity-75">
            <x-icon name="x-mark" class="w-3 h-3" />
        </button>
    @endif
</span>
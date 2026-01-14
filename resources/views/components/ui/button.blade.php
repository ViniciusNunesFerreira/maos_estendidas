@if($href)
    <a href="{{ $href }}" 
       class="inline-flex items-center justify-center font-medium rounded-lg transition-colors
              {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}
              {{ $size === 'sm' ? 'px-3 py-1.5 text-sm' : '' }}
              {{ $size === 'md' ? 'px-4 py-2 text-base' : '' }}
              {{ $size === 'lg' ? 'px-6 py-3 text-lg' : '' }}
              {{ $outline ? 
                 'border-2 bg-transparent hover:bg-'.$variant.'-50' : 
                 'bg-'.$variant.'-600 text-white hover:bg-'.$variant.'-700' 
              }}">
        {{ $slot }}
    </a>
@else
    <button 
        type="{{ $type }}"
        {{ $disabled ? 'disabled' : '' }}
        class="inline-flex items-center justify-center font-medium rounded-lg transition-colors
               {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}
               {{ $size === 'sm' ? 'px-3 py-1.5 text-sm' : '' }}
               {{ $size === 'md' ? 'px-4 py-2 text-base' : '' }}
               {{ $size === 'lg' ? 'px-6 py-3 text-lg' : '' }}
               {{ $outline ? 
                  'border-2 bg-transparent hover:bg-'.$variant.'-50' : 
                  'bg-'.$variant.'-600 text-white hover:bg-'.$variant.'-700' 
               }}">
        {{ $slot }}
    </button>
@endif
{{-- resources/views/components/cards/metric-card.blade.php --}}
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow']) }}>
    <div class="flex items-center justify-between mb-4">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-500 mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ $value }}</p>
        </div>
        
        @if($icon)
            <div class="w-12 h-12 rounded-full {{ $getIconBgColor() }} flex items-center justify-center flex-shrink-0">
                <x-icon :name="$icon" class="w-6 h-6 {{ $getIconTextColor() }}" />
            </div>
        @endif
    </div>
    
    @if($trend || $footer)
        <div class="flex items-center justify-between text-sm">
            @if($trend && $trendValue)
                <div class="flex items-center gap-1">
                    <x-icon :name="$getTrendIcon()" class="w-4 h-4 {{ $getTrendColor() }}" />
                    <span class="font-medium {{ $getTrendColor() }}">{{ $trendValue }}</span>
                </div>
            @endif
            
            @if($footer)
                <div class="text-gray-500">{{ $footer }}</div>
            @endif
        </div>
    @endif
</div>
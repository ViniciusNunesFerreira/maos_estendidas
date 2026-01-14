{{-- resources/views/components/cards/stat-card.blade.php --}}
{{-- 
    StatCard Component - Alias para MetricCard
    Card de estatísticas para KPIs do dashboard
    
    Uso:
    <x-cards.stat-card
        title="Vendas Hoje"
        value="R$ 2.450,00"
        icon="currency-dollar"
        variant="success"
        trend="up"
        trend-value="+12%"
        footer="vs ontem"
    />
--}}

@props([
    'title',
    'value',
    'icon' => null,
    'variant' => 'default',
    'trend' => null,       // 'up', 'down', 'neutral'
    'trendValue' => null,  // '+12%', '-5%'
    'footer' => null,
])

@php
$iconBgClasses = [
    'default' => 'bg-gray-100',
    'primary' => 'bg-blue-100',
    'success' => 'bg-green-100',
    'danger' => 'bg-red-100',
    'warning' => 'bg-yellow-100',
    'info' => 'bg-cyan-100',
];

$iconTextClasses = [
    'default' => 'text-gray-600',
    'primary' => 'text-blue-600',
    'success' => 'text-green-600',
    'danger' => 'text-red-600',
    'warning' => 'text-yellow-600',
    'info' => 'text-cyan-600',
];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow']) }}>
    <div class="flex items-center justify-between mb-4">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-500 mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ $value }}</p>
        </div>
        
        @if($icon)
            <div class="w-12 h-12 rounded-full {{ $iconBgClasses[$variant] ?? $iconBgClasses['default'] }} flex items-center justify-center flex-shrink-0">
                <x-icon :name="$icon" class="w-6 h-6 {{ $iconTextClasses[$variant] ?? $iconTextClasses['default'] }}" />
            </div>
        @endif
    </div>
    
    @if($trend || $footer)
        <div class="flex items-center justify-between text-sm">
            @if($trend && $trendValue)
                <div class="flex items-center gap-1">
                    @if($trend === 'up')
                        <x-icon name="trending-up" class="w-4 h-4 text-green-500" />
                        <span class="text-green-600 font-medium">{{ $trendValue }}</span>
                    @elseif($trend === 'down')
                        <x-icon name="trending-down" class="w-4 h-4 text-red-500" />
                        <span class="text-red-600 font-medium">{{ $trendValue }}</span>
                    @else
                        <x-icon name="minus" class="w-4 h-4 text-gray-400" />
                        <span class="text-gray-500">{{ $trendValue }}</span>
                    @endif
                </div>
            @endif
            
            @if($footer)
                <div class="text-gray-500">{{ $footer }}</div>
            @endif
        </div>
    @endif
</div>
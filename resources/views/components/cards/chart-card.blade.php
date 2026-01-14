{{-- resources/views/components/cards/chart-card.blade.php --}}
{{-- 
    ChartCard Component
    Card com gráfico Chart.js integrado
    
    Uso:
    <x-cards.chart-card
        title="Vendas por Dia"
        chart-id="salesChart"
        :chart-data="$chartData"
    >
        <x-slot name="actions">
            <select class="text-sm border-gray-300 rounded-md">
                <option>7 dias</option>
                <option>30 dias</option>
                <option>90 dias</option>
            </select>
        </x-slot>
    </x-cards.chart-card>
--}}

@props([
    'title',
    'chartId',
    'chartData' => [],
    'chartType' => 'line',    // line, bar, pie, doughnut
    'height' => '300',        // altura em pixels
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-100 p-6']) }}>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
        
        @if(isset($actions))
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
    
    {{-- Chart Container --}}
    <div style="height: {{ $height }}px;">
        <canvas id="{{ $chartId }}"></canvas>
    </div>
    
    {{-- Footer/Legend --}}
    @if(isset($footer))
        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $footer }}
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('{{ $chartId }}');
    
    if (ctx) {
        const chartData = @json($chartData);
        
        new Chart(ctx, {
            type: '{{ $chartType }}',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: {{ isset($showLegend) && $showLegend ? 'true' : 'false' }},
                        position: 'bottom',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                @if($chartType === 'line' || $chartType === 'bar')
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                },
                @endif
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
});
</script>
@endpush
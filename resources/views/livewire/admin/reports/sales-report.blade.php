<div class="space-y-6 bg-gray-50 min-h-screen">
    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Início</label>
                <input type="date" wire:model.live="startDate" class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Fim</label>
                <input type="date" wire:model.live="endDate" class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Agrupar</label>
                <select wire:model.live="groupBy" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="day">Dia</option>
                    <option value="week">Semana</option>
                    <option value="month">Mês</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Origem</label>
                <select wire:model.live="originFilter" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="all">Todas as Origens</option>
                    <option value="pdv">PDV (Balcão)</option>
                    <option value="totem">Totem</option>
                    <option value="app">Aplicativo</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Cards de Resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm text-gray-500 font-medium">Total de Pedidos</span>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($summary['total_orders'] ?? 0, 0, '', '.') }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm text-gray-500 font-medium">Receita Total</span>
            <div class="text-2xl font-bold text-green-600 mt-1">R$ {{ number_format($summary['total_revenue'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm text-gray-500 font-medium">Ticket Médio</span>
            <div class="text-2xl font-bold text-blue-600 mt-1">R$ {{ number_format($summary['average_ticket'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm text-gray-500 font-medium">Clientes Únicos</span>
            <div class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($summary['unique_customers'] ?? 0, 0, '', '.') }}</div>
        </div>
    </div>

    {{-- Gráfico Reativo --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" 
         x-data="{ 
            data: @entangle('chartData').live,
            chart: null,
            initChart() {
                if(this.chart) this.chart.destroy();
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: this.data.map(d => d.period),
                        datasets: [{
                            label: 'Vendas (R$)',
                            data: this.data.map(d => d.revenue),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });
            }
         }" 
         x-init="initChart(); $watch('data', () => initChart())">
        <h3 class="text-lg font-bold text-gray-800 mb-6">Evolução Financeira</h3>
        <div class="h-80"><canvas x-ref="canvas"></canvas></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Origem --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="font-bold text-gray-800 mb-4">Vendas por Canal</h3>
            <div class="space-y-4">
                @foreach($byOrigin as $item)
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-semibold text-gray-600 uppercase">{{ $item['origin'] }}</span>
                            <span class="text-gray-500">{{ $item['orders'] }} pedidos</span>
                        </div>
                        @php $percent = $summary['total_revenue'] > 0 ? ($item['total'] / $summary['total_revenue']) * 100 : 0; @endphp
                        <div class="relative w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div class="absolute top-0 left-0 h-full bg-blue-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="text-right text-xs font-bold text-gray-700 mt-1">R$ {{ number_format($item['total'], 2, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Métodos de Pagamento --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="font-bold text-gray-800 mb-4">Meios de Pagamento</h3>
            <div class="grid grid-cols-2 gap-4">
                @foreach($byPaymentMethod as $method => $data)
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 text-center">
                        <span class="text-xs text-gray-400 uppercase font-bold">{{ $method }}</span>
                        <div class="text-lg font-bold text-gray-800 mt-1">R$ {{ number_format($data['total'], 2, ',', '.') }}</div>
                        <div class="text-xs text-blue-500">{{ $data['count'] }} Transações</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
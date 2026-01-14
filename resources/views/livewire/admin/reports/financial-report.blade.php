<div class="p-6 bg-gray-50 min-h-screen">
    <div class="space-y-6">
        {{-- Header e Filtros --}}
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Visão Financeira</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Inicial</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Final</label>
                    <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Visualização</label>
                    <select wire:model.live="reportType" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="revenue">Consolidado (Vendas + Faturas)</option>
                        <option value="invoices">Apenas Faturas</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="exportReport" 
                            wire:loading.attr="disabled"
                            class="w-full flex justify-center items-center px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition shadow-sm disabled:opacity-50">
                        <svg wire:loading wire:target="exportReport" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span wire:loading.remove wire:target="exportReport">Exportar Excel (CSV)</span>
                        <span wire:loading wire:target="exportReport">Processando...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Cards KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow-sm border-l-4 border-l-green-500 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Receita Recebida</p>
                        <p class="mt-2 text-2xl font-extrabold text-green-600">R$ {{ number_format($totalRevenue, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-2 bg-green-50 rounded-lg text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm border-l-4 border-l-blue-500 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Faturas Pagas</p>
                        <p class="mt-2 text-2xl font-extrabold text-blue-600">{{ $paidInvoicesCount }}</p>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm border-l-4 border-l-yellow-500 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">A Receber</p>
                        <p class="mt-2 text-2xl font-extrabold text-yellow-600">R$ {{ number_format($pendingAmount, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-2 bg-yellow-50 rounded-lg text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm border-l-4 border-l-red-500 rounded-xl p-6">
                 <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Inadimplência</p>
                        <p class="mt-2 text-2xl font-extrabold text-red-600">R$ {{ number_format($overdueAmount, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-2 bg-red-50 rounded-lg text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráfico --}}
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6" 
             x-data="{ 
                chart: null,
                initChart() {
                    const ctx = this.$refs.canvas.getContext('2d');
                    if (this.chart) this.chart.destroy();

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @entangle('revenueByDay').live.map(i => i.date),
                            datasets: [{
                                label: 'Receita Diária (R$)',
                                data: @entangle('revenueByDay').live.map(i => i.total),
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderColor: '#10b981',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointHoverRadius: 6
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { borderDash: [2, 4], color: '#f3f4f6' }
                                },
                                x: {
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
             }"
             x-init="initChart()"
             x-effect="
                if(chart) {
                    const data = $wire.revenueByDay;
                    chart.data.labels = data.map(i => i.date);
                    chart.data.datasets[0].data = data.map(i => i.total);
                    chart.update();
                }
             ">
            <h4 class="text-lg font-bold text-gray-800 mb-6">Evolução de Caixa</h4>
            <div class="h-80"><canvas x-ref="canvas"></canvas></div>
        </div>

        {{-- Tabela Detalhada --}}
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Movimentações Recentes</h4>
                <span class="text-xs text-gray-400">Limitado aos últimos 100 registros</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Referência</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData as $item)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item['date'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">{{ $item['ref'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $item['type_color'] }}">
                                    {{ $item['description'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                    R$ {{ number_format($item['amount'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold 
                                        {{ strtolower($item['status']) === 'paga' || strtolower($item['status']) === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">
                                    Nenhuma movimentação encontrada neste período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
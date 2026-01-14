<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Vendas dos Últimos Dias</h3>
        
        <select wire:model="period" class="text-sm border-gray-300 rounded-md">
            <option value="7">7 dias</option>
            <option value="15">15 dias</option>
            <option value="30">30 dias</option>
        </select>
    </div>
    
    <div class="h-64">
        <canvas id="salesChart"></canvas>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @js($chartData['labels']),
                    datasets: [{
                        label: 'Vendas (R$)',
                        data: @js($chartData['values']),
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });
            
            // Atualizar chart quando period mudar
            Livewire.on('refreshStats', () => {
                chart.data.labels = @js($chartData['labels']);
                chart.data.datasets[0].data = @js($chartData['values']);
                chart.update();
            });
        });
    </script>
    @endpush
</div>
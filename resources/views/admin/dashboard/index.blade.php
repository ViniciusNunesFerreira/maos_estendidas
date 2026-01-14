<x-layouts.admin title="Dashboard">
    <div class="space-y-6">
        <!-- Métricas Principais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <livewire:admin.dashboard.sales-card />
            <livewire:admin.dashboard.orders-card />
            <livewire:admin.dashboard.active-filhos-card />
            <livewire:admin.dashboard.overdue-invoices-card />
        </div>
        
        <!-- Gráfico de Vendas -->
        <livewire:admin.dashboard.sales-chart />
        
        <!-- Grid de Informações -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top 10 Produtos -->
            <livewire:admin.dashboard.top-products />
            
            <!-- Pedidos Recentes -->
            <livewire:admin.dashboard.recent-orders />
        </div>
        
        <!-- Alertas de Estoque -->
        <livewire:admin.dashboard.stock-alerts />

    </div>
</x-layouts.admin>
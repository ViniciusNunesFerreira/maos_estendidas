{{-- resources/views/admin/orders/index.blade.php --}}
<x-layouts.admin title="Pedidos">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pedidos</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Acompanhe e gerencie todos os pedidos
                </p>
            </div>
        </div>

        {{-- Estatísticas Rápidas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-cards.stat-card 
                title="Pedidos Hoje" 
                :value="$stats['today']['count']" 
                icon="shopping-bag" 
                color="blue" 
            />
            <x-cards.stat-card 
                title="Receita Hoje" 
                :value="'R$ ' . number_format($stats['today']['total'], 2, ',', '.')" 
                icon="dollar-sign" 
                color="green" 
            />
            <x-cards.stat-card 
                title="Em Andamento" 
                :value="$stats['pending']" 
                icon="clock" 
                color="yellow" 
            />
            <x-cards.stat-card 
                title="Finalizados Hoje" 
                :value="$stats['completed_today']" 
                icon="check-circle" 
                color="gray" 
            />
        </div>

        {{-- Lista de Pedidos --}}
        <livewire:admin.orders.orders-list />
    </div>
</x-layouts.admin>
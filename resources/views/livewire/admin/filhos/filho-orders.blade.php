<div>
    <div class="bg-white shadow sm:rounded-lg">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Pedidos do Filho</h3>
                    <p class="mt-1 text-sm text-gray-500">Histórico de pedidos de {{ $filho->name }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        <option value="completed">Concluído</option>
                        <option value="pending">Pendente</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Inicial</label>
                    <input type="date" wire:model.live="filterDateFrom" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Final</label>
                    <input type="date" wire:model.live="filterDateTo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nº pedido..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 bg-gray-50 border-b">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Total de Pedidos</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['total_orders'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Valor Total</p>
                <p class="mt-1 text-2xl font-bold text-green-600">R$ {{ number_format($summary['total_spent'], 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Ticket Médio</p>
                <p class="mt-1 text-2xl font-bold text-blue-600">R$ {{ number_format($summary['average_ticket'], 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Este Mês</p>
                <p class="mt-1 text-2xl font-bold text-purple-600">{{ $summary['this_month'] }}</p>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <div class="px-6 py-6">
            @if($orders->count() > 0)
                <div class="space-y-4">
                    @foreach($orders as $order)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-10 w-10 rounded-full 
                                            @if($order->status === 'completed') bg-green-100
                                            @elseif($order->status === 'pending') bg-yellow-100
                                            @else bg-red-100 @endif
                                            flex items-center justify-center">
                                            <svg class="h-5 w-5 
                                                @if($order->status === 'completed') text-green-600
                                                @elseif($order->status === 'pending') text-yellow-600
                                                @else text-red-600 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <h4 class="text-base font-medium text-gray-900">#{{ $order->order_number }}</h4>
                                                <span class="px-2 py-1 text-xs rounded-full
                                                    @if($order->status === 'completed') bg-green-100 text-green-800
                                                    @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                                    @else bg-red-100 text-red-800 @endif">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-sm text-gray-500">
                                                {{ $order->created_at->format('d/m/Y H:i') }} • {{ $order->items_count }} itens
                                                @if($order->source)
                                                    • Origem: {{ ucfirst($order->source) }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-6 flex flex-col items-end space-y-2">
                                    <p class="text-xl font-bold text-gray-900">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</p>
                                    <button wire:click="viewOrder('{{ $order->id }}')" class="text-sm text-blue-600 hover:text-blue-800">
                                        Ver detalhes →
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $orders->links() }}</div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Nenhum pedido encontrado</p>
                </div>
            @endif
        </div>
    </div>
</div>
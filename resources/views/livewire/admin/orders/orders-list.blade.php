{{-- resources/views/livewire/admin/orders/orders-list.blade.php --}}
<div>
    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Buscar pedido ou cliente..."
                    class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                >
            </div>
            
            <div>
                <select wire:model.live="statusFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todos os status</option>
                    <option value="pending">Pendente</option>
                    <option value="preparing">Preparando</option>
                    <option value="ready">Pronto</option>
                    <option value="delivered">Entregue</option>
                     <option value="completed">Completo/Pago</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </div>
            
            <div>
                <select wire:model.live="originFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todas as origens</option>
                    <option value="pdv">PDV</option>
                    <option value="totem">Totem</option>
                    <option value="app">App</option>
                </select>
            </div>
            
            <div>
                <select wire:model.live="periodFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="today">Hoje</option>
                    <option value="yesterday">Ontem</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mês</option>
                    <option value="all">Todos</option>
                </select>
            </div>
            
            <div>
                <select wire:model.live="customerType" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todos os clientes</option>
                    <option value="filho">Filhos</option>
                    <option value="guest">Visitantes</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Lista de Pedidos --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedido</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Itens</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                    #{{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($order->filho)
                                        <div class="flex-shrink-0 h-8 w-8">
                                            @if($order->filho->photo_url)
                                                <img class="h-8 w-8 rounded-full object-cover" src="{{ Storage::url($order->filho->photo_url) }}" alt="">
                                            @else
                                                <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-primary-600">{{ substr($order->filho->full_name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ Str::limit($order->filho->full_name, 20) }}</p>
                                            <p class="text-xs text-gray-500">Filho</p>
                                        </div>
                                    @else
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                                            <x-icon name="user" class="h-4 w-4 text-gray-400" />
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $order->guest_name ?? 'Visitante' }}</p>
                                            <p class="text-xs text-gray-500">Visitante</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $order->origin === 'pdv' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $order->origin === 'totem' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $order->origin === 'app' ? 'bg-green-100 text-green-800' : '' }}
                                ">
                                    {{ strtoupper($order->origin) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'item' : 'itens' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                R$ {{ number_format($order->total, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'yellow',
                                        'confirmed' => 'blue',
                                        'preparing' => 'indigo',
                                        'ready' => 'green',
                                        'completed' => 'gray',
                                        'cancelled' => 'red',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pendente',
                                        'confirmed' => 'Confirmado',
                                        'preparing' => 'Preparando',
                                        'ready' => 'Pronto',
                                        'completed' => 'Entregue',
                                        'cancelled' => 'Cancelado',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColors[$order->status] ?? 'gray' }}-100 text-{{ $statusColors[$order->status] ?? 'gray' }}-800">
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-primary-600 hover:text-primary-900">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <x-icon name="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                                <p>Nenhum pedido encontrado</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $orders->links() }}
        </div>
    </div>
</div>
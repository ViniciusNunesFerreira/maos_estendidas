{{-- resources/views/livewire/admin/dashboard/recent-orders.blade.php --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Pedidos Recentes</h3>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
            Ver todos
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="pb-3">Pedido</th>
                    <th class="pb-3">Cliente</th>
                    <th class="pb-3">Total</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3">Hora</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3">
                            <a href="{{ route('admin.orders.show', $order['id']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                #{{ $order['order_number'] }}
                            </a>
                        </td>
                        <td class="py-3">
                            <span class="text-sm text-gray-900">{{ Str::limit($order['customer_name'], 20) }}</span>
                        </td>
                        <td class="py-3">
                            <span class="text-sm font-medium text-gray-900">R$ {{ number_format($order['total'], 2, ',', '.') }}</span>
                        </td>
                        <td class="py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $order['status_color'] }}-100 text-{{ $order['status_color'] }}-800">
                                {{ $order['status_label'] }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="text-xs text-gray-500">{{ $order['created_at'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">
                            <x-icon name="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                            <p>Nenhum pedido recente</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
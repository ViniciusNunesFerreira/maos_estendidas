<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
            <h3 class="text-lg font-semibold text-gray-900">Alerta de Pedidos no APP</h3>
            @if($totalOrdersAlerts > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                    {{ $totalOrdersAlerts }}
                </span>
            @endif
        </div>
    </div>
    
    
    @if($totalOrdersAlerts > 0)
        <div>
            <h4 class="text-sm font-medium text-yellow-700 mb-2 flex items-center">
                <x-icon name="alert-triangle" class="w-4 h-4 mr-1" />
                Atenção!! Alguns Pedidos precisam ser Reservados...
            </h4>
            <div class="space-y-2">
                @foreach($orders as $order)
                    <div class="flex items-center justify-between p-2 bg-yellow-50 rounded-lg">
                        <div>
                            <span class="text-sm text-gray-900">{{ Str::limit($order['customer_name'], 20) }}</span> <br>
                            <span class="text-xs text-gray-500 ml-2">Pedido N. :{{ $order['order_number'] }}</span>
                        </div>
                        <div>
                            <a href="{{ route('admin.orders.show', $order['id'] ) }}" class="text-sm font-mono text-green-800 p-2 bg-green-100">VER PEDIDO</a>
                        </div>
                        
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    @if($totalOrdersAlerts === 0)
        <div class="text-center py-8 text-gray-500">
            <x-icon name="check-circle" class="w-12 h-12 mx-auto mb-2 text-green-300" />
            <p>Nenhum pedido feito para reserva!</p>
        </div>
    @endif
</div>

<div wire:poll.10s>
    <div class="flex space-x-4 items-center mb-4">
        <h2 class="text-lg font-semibold">Monitor de Pedidos App</h2>
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
        </span>
    </div>

    @if($orders->isEmpty())
    
        <div class="p-4 relative border rounded-lg transition-all duration-500 bg-white shadow-sm w-full"> 
            <p class="text-gray-500 italic mb-4">Nenhum pedido pendente no momento...</p>
        </div>
       
    @else
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($orders as $order)
                <div wire:key="order-{{ $order->id }}" class="p-4 relative border rounded-lg transition-all duration-500 hover:bg-gray-50 bg-white shadow-sm">
                    <div class="absolute -top-3 -right-3 bg-green-100 px-2 rounded-lg">
                        <span class="text-green-600 font-bold">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-bold pt-2">#{{ $order->order_number }}</span>
                        
                    </div>
                    <p class="text-sm text-gray-600">{{ Str::limit($order->customer_name, 20) }}</p>
                    <div class="my-2 text-xs text-gray-400 flex justify-between">
                        <span>{{ $order->payment_method_chosen }}</span>
                        <span>{{ $order->created_at->diffForHumans() }}</span>
                        
                    </div>
                    <div class="mt-2 flex flex-row-reverse">
                        <a href="{{ route('admin.orders.show', $order['id'] ) }}" class="text-sm font-mono text-green-800 py-2 px-4 mx-auto bg-green-100 ">VER PEDIDO</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

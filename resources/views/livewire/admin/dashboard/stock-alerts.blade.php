<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
            <h3 class="text-lg font-semibold text-gray-900">Alertas de Estoque</h3>
            @if($totalAlerts > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                    {{ $totalAlerts }}
                </span>
            @endif
        </div>
        <a href="{{ route('admin.stock.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
            Gerenciar
        </a>
    </div>
    
    @if(count($outOfStockProducts) > 0)
        <div class="mb-4">
            <h4 class="text-sm font-medium text-red-700 mb-2 flex items-center">
                <x-icon name="x-circle" class="w-4 h-4 mr-1" />
                Sem Estoque
            </h4>
            <div class="space-y-2">
                @foreach($outOfStockProducts as $product)
                    <div class="flex items-center justify-between p-2 bg-red-50 rounded-lg">
                        <span class="text-sm text-gray-900">{{ Str::limit($product['name'], 25) }}</span>
                        <span class="text-xs font-mono text-gray-500">{{ $product['sku'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    @if(count($lowStockProducts) > 0)
        <div>
            <h4 class="text-sm font-medium text-yellow-700 mb-2 flex items-center">
                <x-icon name="alert-triangle" class="w-4 h-4 mr-1" />
                Estoque Baixo
            </h4>
            <div class="space-y-2">
                @foreach($lowStockProducts as $product)
                    <div class="flex items-center justify-between p-2 bg-yellow-50 rounded-lg">
                        <div>
                            <span class="text-sm text-gray-900">{{ Str::limit($product['name'], 20) }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ $product['stock'] }}/{{ $product['stock_min'] }}</span>
                        </div>
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ min($product['percentage'], 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    @if($totalAlerts === 0)
        <div class="text-center py-8 text-gray-500">
            <x-icon name="check-circle" class="w-12 h-12 mx-auto mb-2 text-green-300" />
            <p>Todos os estoques estão OK!</p>
        </div>
    @endif
</div>
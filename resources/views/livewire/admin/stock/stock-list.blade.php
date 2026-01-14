{{-- resources/views/livewire/admin/stock/stock-list.blade.php --}}
<div>
    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Total de Produtos</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_products']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Sem Estoque</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($stats['out_of_stock']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Estoque Baixo</p>
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['low_stock']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Valor em Estoque</p>
            <p class="text-2xl font-bold text-green-600">R$ {{ number_format($stats['total_value'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Buscar produto ou SKU..."
                    class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                >
            </div>
            
            <div>
                <select wire:model.live="statusFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todos os status</option>
                    <option value="ok">Estoque OK</option>
                    <option value="low">Estoque Baixo</option>
                    <option value="out">Sem Estoque</option>
                </select>
            </div>
            
            <div>
                <select wire:model.live="categoryFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todas as categorias</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <select wire:model.live="locationFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todos os locais</option>
                    <option value="loja">Loja</option>
                    <option value="cantina">Cantina</option>
                    <option value="ambos">Ambos</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Lista de Produtos --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estoque</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Mínimo</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($products as $product)
                        @php
                            $isOutOfStock = $product->stock_quantity === 0;
                            $isLowStock = $product->stock_quantity > 0 && $product->stock_quantity <= $product->min_stock_alert;
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $isOutOfStock ? 'bg-red-50' : ($isLowStock ? 'bg-yellow-50' : '') }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    
                                    
                                    <x-product-image :product="$product" size="thumb" />
                                    
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ Str::limit($product->name, 30) }}</p>
                                        <p class="text-xs text-gray-500">{{ ucfirst($product->location) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                {{ $product->sku }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $product->category?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-lg font-bold {{ $isOutOfStock ? 'text-red-600' : ($isLowStock ? 'text-yellow-600' : 'text-gray-900') }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                {{ $product->min_stock_alert }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($isOutOfStock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Sem Estoque
                                    </span>
                                @elseif($isLowStock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Baixo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        OK
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                R$ {{ number_format($product->stock_quantity * ($product->cost_price ?? 0), 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <x-icon name="package" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                                <p>Nenhum produto encontrado</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $products->links() }}
        </div>
    </div>
</div>
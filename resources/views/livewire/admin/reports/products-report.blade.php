<div class="space-y-6">
    {{-- Filtros --}}
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Ordenação</label>
                <select wire:model.live="reportType" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="sales">Mais Vendidos (Qtd)</option>
                    <option value="revenue">Maior Receita (R$)</option>
                    <option value="stock">Nível de Estoque</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Categoria</label>
                <select wire:model.live="categoryFilter" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas as Categorias</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Início</label>
                    <input type="date" wire:model.live="startDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Fim</label>
                    <input type="date" wire:model.live="endDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex items-end">
                <button wire:click="exportReport" 
                        wire:loading.attr="disabled"
                        class="w-full flex justify-center items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition disabled:opacity-50 disabled:cursor-wait shadow-sm">
                    <svg wire:loading wire:target="exportReport" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span wire:loading.remove wire:target="exportReport">Exportar Excel (CSV)</span>
                    <span wire:loading wire:target="exportReport">Gerando...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Cards KPI --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Vendido (Qtd)</p>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">{{ number_format($totalSold, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Receita Gerada</p>
            <p class="mt-2 text-3xl font-extrabold text-green-600">R$ {{ number_format($totalRevenue, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Produtos Ativos</p>
            <p class="mt-2 text-3xl font-extrabold text-blue-600">{{ $activeProducts }}</p>
        </div>
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Estoque Crítico</p>
            <p class="mt-2 text-3xl font-extrabold text-red-600">{{ $lowStockProducts }}</p>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Produto</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Qtd. Vendida</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Receita</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Estoque</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-blue-50/50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                        @if(!empty($product['image_url']))
                                            <img src="{{ asset('storage/'.$product['image_url']) }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="h-full w-full flex items-center justify-center text-gray-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900">{{ $product['name'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $product['sku'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 font-medium">
                                {{ number_format($product['total_sold'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-green-600">
                                R$ {{ number_format($product['total_revenue'], 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                @php 
                                    $stock = $product['stock_quantity'];
                                    $min = $product['min_stock_alert'];
                                    $statusClass = $stock <= 0 ? 'bg-red-100 text-red-800' : ($stock <= $min ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusClass }}">
                                    {{ $stock }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <p class="text-sm">Nenhum produto encontrado para este período.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
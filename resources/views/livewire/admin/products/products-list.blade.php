<!-- resources/views/livewire/product-list.blade.php -->
<div>
    <div class="bg-white shadow sm:rounded-lg">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Produtos</h3>
                <a href="{{ route('admin.products.create') }}"  class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                   <x-icon name="plus" class="w-4 h-4 mr-2" />
                   Novo Produto
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="px-6 py-4 bg-gray-50 border-b">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar..." class="rounded-md border-gray-300">
                <select wire:model.live="filterCategory" class="rounded-md border-gray-300">
                    <option value="">Todas Categorias</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterType" class="rounded-md border-gray-300">
                    <option value="">Todos Tipos</option>
                    <option value="loja">Loja</option>
                    <option value="cantina">Cantina</option>
                </select>
                <select wire:model.live="filterStatus" class="rounded-md border-gray-300">
                    <option value="">Todos Status</option>
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>
        </div>

        <!-- Tabela de Produtos -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Estoque</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <!-- <x-product-image :product="$product" size="square" class="hover:scale-105 transition" /> -->
                                    <x-product-image :product="$product" size="thumb" />
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                        <div class="text-xs text-gray-500">SKU: {{ $product->sku }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $product->category->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right">R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                <span class="{{ $product->stock_quantity <= $product->min_stock_alert ? 'text-red-600 font-bold' : '' }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 text-xs rounded-full {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $product->is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-900">Editar</button>
                                <button wire:click="deleteProduct('{{ $product->id }}')" wire:confirm="Confirma?" class="text-red-600 hover:text-red-900">Excluir</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">Nenhum produto encontrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $products->links() }}</div>
    </div>
</div>
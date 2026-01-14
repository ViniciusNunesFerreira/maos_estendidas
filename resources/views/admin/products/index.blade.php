<x-layouts.admin title="Produtos">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Produtos</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Gerencie o catálogo de produtos da loja e cantina
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <x-icon name="folder" class="w-4 h-4 mr-2" />
                    Categorias
                </a>
                <a href="{{ route('admin.products.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
                    <x-icon name="plus" class="w-4 h-4 mr-2" />
                    Novo Produto
                </a>
            </div>
        </div>

        {{-- Estatísticas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-cards.stat-card 
                title="Total de Produtos" 
                :value="$stats['total'] ?? 0" 
                icon="shopping-bag" 
                variant="primary" 
            />
            <x-cards.stat-card 
                title="Ativos" 
                :value="$stats['active'] ?? 0" 
                icon="check-circle" 
                variant="success" 
            />
            <x-cards.stat-card 
                title="Estoque Baixo" 
                :value="$stats['low_stock'] ?? 0" 
                icon="bell-alert" 
                variant="warning" 
            />
            <x-cards.stat-card 
                title="Sem Estoque" 
                :value="$stats['out_of_stock'] ?? 0" 
                icon="exclamation-triangle" 
                variant="danger" 
            />
        </div>

        {{-- Lista de Produtos --}}
        <livewire:admin.products.products-list />
    </div>
</x-layouts.admin>
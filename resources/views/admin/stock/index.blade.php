<x-layouts.admin title="Estoque">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestão de Estoque</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Controle de entradas, saídas e movimentações de estoque
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.stock.entry') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <x-icon name="arrow-down" class="w-4 h-4 mr-2 text-green-600" />
                    Entrada
                </a>
                <a href="{{ route('admin.stock.out') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <x-icon name="arrow-up" class="w-4 h-4 mr-2 text-red-600" />
                    Saída
                </a>
            </div>
        </div>

        {{-- Lista de Estoque --}}
        <livewire:admin.stock.stock-list />
    </div>
</x-layouts.admin>
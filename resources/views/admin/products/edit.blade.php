<x-layouts.admin title="Editar Produto">
    <div class="space-y-6">
        {{-- Breadcrumbs --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                        <x-icon name="home" class="w-4 h-4" />
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <x-icon name="chevron-right" class="w-4 h-4 text-gray-400" />
                        <a href="{{ route('admin.products.index') }}" class="ml-1 text-sm text-gray-500 hover:text-gray-700">Produtos</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <x-icon name="chevron-right" class="w-4 h-4 text-gray-400" />
                        <span class="ml-1 text-sm text-gray-900 font-medium">Editar: {{ $product->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Editar Produto</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Atualize as informações do produto <strong>{{ $product->sku }}</strong>
                </p>
            </div>
            
            <div class="flex space-x-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $product->is_active ? 'Ativo' : 'Inativo' }}
                </span>
            </div>
        </div>

        
        <livewire:admin.products.product-form :product="$product" />
    </div>
</x-layouts.admin>
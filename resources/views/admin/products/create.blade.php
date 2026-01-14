{{-- resources/views/admin/products/create.blade.php --}}
<x-layouts.admin title="Novo Produto">
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
                        <span class="ml-1 text-sm text-gray-900 font-medium">Novo</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Novo Produto</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Cadastre um novo produto no sistema
                </p>
            </div>
        </div>

        {{-- Formulário --}}
        <livewire:admin.products.product-form />
    </div>
</x-layouts.admin>
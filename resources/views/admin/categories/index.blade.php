<x-layouts.admin>
    <x-slot name="title">Categorias</x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gerenciar Categorias</h1>
            <p class="mt-1 text-sm text-gray-600">
                Organize os produtos em categorias para melhor controle
            </p>
        </div>

        {{-- Componente Livewire --}}
        <div class="bg-white rounded-lg shadow">
            <livewire:admin.products.category-manager />
        </div>
    </div>
</x-layouts.admin>
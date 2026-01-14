<x-layouts.admin>
    <x-slot name="title">Relatório de Produtos</x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Relatório de Produtos</h1>
            <p class="mt-1 text-sm text-gray-600">
                Análise de desempenho e movimentação de produtos
            </p>
        </div>

        {{-- Componente Livewire --}}
        <livewire:admin.reports.products-report />
    </div>
</x-layouts.admin>
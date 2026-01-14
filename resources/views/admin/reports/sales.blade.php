<x-layouts.admin>
    <x-slot name="title">Relatório de Vendas</x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Relatório de Vendas</h1>
            <p class="mt-1 text-sm text-gray-600">
                Análise detalhada das vendas por período
            </p>
        </div>

        {{-- Componente Livewire --}}
        <livewire:admin.reports.sales-report />
    </div>
</x-layouts.admin>
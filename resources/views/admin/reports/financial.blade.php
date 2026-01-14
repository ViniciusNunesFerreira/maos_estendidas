<x-layouts.admin>
    <x-slot name="title">Relatório Financeiro</x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Relatório Financeiro</h1>
            <p class="mt-1 text-sm text-gray-600">
                Análise completa de receitas, despesas e faturamento
            </p>
        </div>

        {{-- Componente Livewire --}}
        <livewire:admin.reports.financial-report />
    </div>
</x-layouts.admin>
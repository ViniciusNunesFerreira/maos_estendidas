{{-- resources/views/admin/invoices/index.blade.php --}}
<x-layouts.admin title="Faturas">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Faturas</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Gerencie faturas de consumo e assinaturas dos filhos
                </p>
            </div>
        </div>

        {{-- Lista de Faturas --}}
        <livewire:admin.invoices.invoices-list />
    </div>
</x-layouts.admin>
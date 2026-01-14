<x-layouts.admin>
    <x-slot name="title">Detalhes da Fatura</x-slot>

    <div class="space-y-6">
        {{-- Breadcrumbs --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.invoices.index') }}" class="text-gray-700 hover:text-blue-600">
                        Faturas
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <x-icon name="chevron-right" class="w-4 h-4 text-gray-400" />
                        <span class="ml-1 text-gray-500">Detalhes</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Componente Livewire --}}
        <livewire:admin.invoices.invoice-details :invoice="$invoice" />
    </div>
</x-layouts.admin>
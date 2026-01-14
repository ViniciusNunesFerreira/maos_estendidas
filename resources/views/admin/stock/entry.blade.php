{{-- resources/views/admin/stock/entry.blade.php --}}
<x-layouts.admin>
    <x-slot name="title">Entrada de Estoque</x-slot>

    <div class="mx-auto">
        {{-- Breadcrumbs --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.stock.index') }}" class="text-gray-700 hover:text-blue-600">
                        Estoque
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <x-icon name="chevron-right" class="w-4 h-4 text-gray-400" />
                        <span class="ml-1 text-gray-500">Entrada</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Entrada de Estoque</h1>
            <p class="mt-1 text-sm text-gray-600">
                Registre a entrada de produtos no estoque
            </p>
        </div>

        {{-- Componente Livewire --}}
        <div class="bg-white rounded-lg shadow p-6">
            <livewire:admin.stock.stock-entry />
        </div>
    </div>
</x-layouts.admin>
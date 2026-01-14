<x-layouts.admin>
    <x-slot name="title">Assinaturas</x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gerenciar Assinaturas</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Acompanhe as assinaturas mensais dos filhos (Mensalidades)
                </p>
            </div>

            <a href="{{ route('admin.subscriptions.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                <x-icon name="plus" class="w-4 h-4 mr-2" />
                Nova Assinatura
            </a>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-icon name="check-circle" class="h-8 w-8 text-green-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Ativas</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $activeCount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-icon name="pause-circle" class="h-8 w-8 text-yellow-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pausadas</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $pausedCount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-icon name="x-circle" class="h-8 w-8 text-red-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Canceladas</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $cancelledCount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-icon name="currency-dollar" class="h-8 w-8 text-blue-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Receita Mensal</p>
                        <p class="text-2xl font-semibold text-gray-900">R$ {{ number_format(($activeCount ?? 0) * 350, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de Assinaturas --}}
        <div class="bg-white rounded-lg shadow">
            <livewire:admin.subscriptions.subscriptions-list />
        </div>
    </div>
</x-layouts.admin>
<x-layouts.admin title="Aprovação de Filhos">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Aprovação de Cadastros</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Gerencie as solicitações de cadastro pendentes
                </p>
            </div>
            
            <a href="{{ route('admin.filhos.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <x-icon name="arrow-left" class="w-4 h-4 mr-2" />
                Voltar para lista
            </a>
        </div>

        <livewire:admin.filhos.approval-queue />
    </div>
</x-layouts.admin>
<x-layouts.admin title="Filhos">
    <!-- Header com Ações -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestão de Filhos</h1>
            <p class="mt-1 text-sm text-gray-600">
                Gerencie os filhos cadastrados na instituição
            </p>
        </div>
        
        <div class="flex space-x-3">
            <x-button 
                variant="secondary"
                href="{{ route('admin.filhos.approval') }}"
                :badge="$pendingApprovals">
                Aprovações Pendentes
            </x-button>
            
            <x-button href="{{ route('admin.filhos.create') }}">
                <x-icon name="plus" class="h-5 w-5 mr-2" />
                Novo Filho
            </x-button>
        </div>
    </div>
    
    <!-- Componente Livewire de Listagem -->
    <livewire:admin.filhos.filhos-list />
</x-layouts.admin>
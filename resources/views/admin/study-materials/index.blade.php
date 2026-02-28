<x-layouts.admin title="Materiais de Estudo">

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"> Gerenciar Material de Estudo</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Gerencie todo o conteúdo disponível para o app dos Filhos.
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <x-icon name="folder" class="w-4 h-4 mr-2" />
                    Categorias
                </a>
                <a href="{{ route('admin.materials.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
                    <x-icon name="plus" class="w-4 h-4 mr-2" />
                    Novo Material
                </a>
            </div>
        </div>

        <livewire:admin.study-materials.study-material-list />

    </div>

</x-layouts.admin>
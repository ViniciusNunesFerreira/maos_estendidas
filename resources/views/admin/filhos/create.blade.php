<x-layouts.admin title="Cadastrar Filho">
    <div class="space-y-6">
        <!-- Breadcrumbs -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li>
                    <a href="{{ route('admin.filhos.index') }}" class="text-gray-700 hover:text-primary-600">
                        Filhos
                    </a>
                </li>
                <li>
                    <span class="text-gray-500">/</span>
                </li>
                <li class="text-gray-500">Novo Cadastro</li>
            </ol>
        </nav>
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Novo Filho</h1>
            <p class="mt-1 text-sm text-gray-600">
                Preencha os dados do filho para cadastro na instituição
            </p>
        </div>
        
        <!-- Formulário Livewire -->
        <livewire:admin.filhos.filho-form />
    </div>
</x-layouts.admin>
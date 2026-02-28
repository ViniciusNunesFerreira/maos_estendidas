<div>
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-6 py-5 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Gerenciar Categorias</h3>
                <button wire:click="openCreateModal" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition flex items-center">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Nova Categoria
                </button>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-b">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar categoria..." class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div>
                    <select wire:model.live="filterStatus" class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Todos os status</option>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="px-6 py-6 relative">
            <div wire:loading.flex wire:target="search, filterStatus, delete, toggleStatus" class="absolute inset-0 bg-white/50 z-10 justify-center items-start pt-10 backdrop-blur-sm">
                <div class="spinner"></div>
            </div>

            @if($categories->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($categories as $category)
                        <div class="bg-white border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 transition group relative">
                            <div class="absolute top-0 right-0 w-3 h-3 rounded-bl-lg rounded-tr-lg" style="background-color: {{ $category->color ?? '#cbd5e1' }}"></div>

                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($category->icon)
                                            <span class="text-gray-400 text-lg">{{ $category->icon }}</span>

                                            <x-icon name="{{ $category->icon }}" class="w-6 h-6 text-gray-400" />
                                        @endif
                                        <h4 class="text-base font-medium text-gray-900">{{ $category->name }}</h4>
                                    </div>
                                    
                                    @if($category->description)
                                        <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $category->description }}</p>
                                    @endif

                                    <div class="mt-3 flex items-center flex-wrap gap-2">
                                        <button wire:click="toggleStatus('{{ $category->id }}')" 
                                            class="px-2 py-1 text-xs rounded-full transition cursor-pointer hover:opacity-80
                                            {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $category->is_active ? 'Ativo' : 'Inativo' }}
                                        </button>

                                        @if($category->parent)
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                                Sub de: {{ $category->parent->name }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <p class="mt-2 text-xs text-gray-400 font-medium">
                                        @if($category->type === 'product')
                                            {{ $category->products_count }} produtos vinculados
                                        @else
                                            {{ $category->study_materials_count }} materiais vinculados
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-col space-y-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                    <button wire:click="openEditModal('{{ $category->id }}')" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button wire:click="delete('{{ $category->id }}')" wire:confirm="Tem certeza que deseja excluir esta categoria?" class="p-1 text-red-600 hover:bg-red-50 rounded" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $categories->links() }}
                </div>
            @else
                <div class="text-center py-12 flex flex-col items-center justify-center">
                    <div class="bg-gray-100 rounded-full p-3 mb-3">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900">Nenhuma categoria encontrada</h3>
                    <p class="mt-1 text-sm text-gray-500">Comece criando uma nova categoria ou ajuste os filtros.</p>
                </div>
            @endif
        </div>
    </div>

    <x-modal wire:model="showModal" name="category-modal" title="{{ $isEditing ? 'Editar Categoria' : 'Nova Categoria' }}">
        <form wire:submit.prevent="save" class="space-y-4">
            
            <x-forms.input 
                label="Nome da Categoria" 
                wire:model="name" 
                name="name" 
                id="name"
                placeholder="Ex: Bebidas, Lanches..." 
                required 
            />

            <x-forms.textarea 
                label="Descrição" 
                wire:model="description" 
                name="description" 
                id="description"
                rows="3" 
                placeholder="Uma breve descrição para exibição no catálogo..." 
            />

            <div class="grid grid-cols-1 gap-4">

                <x-forms.select 
                    label="Tipo de Categoria" 
                    wire:model="type" 
                    name="type"
                    id="type"
                    :options="['product' => 'Produto', 'study_material' => 'Material de Estudo']"
                />

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-forms.select 
                    label="Categoria Pai (Opcional)" 
                    wire:model="parent_id" 
                    name="parent_id"
                    id="parent_id"
                    placeholder="Nenhuma (Categoria Principal)"
                    :options="$parentCategories->pluck('name', 'id')"
                />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Cor de Identificação</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" wire:model.live="color" class="h-9 w-14 p-0 block bg-white border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm cursor-pointer">
                        <span class="text-sm text-gray-500">{{ $color }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-forms.input 
                    label="Ícone (Emoji ou Classe)" 
                    wire:model="icon" 
                    name="icon" 
                    placeholder="Ex: 🍔 ou fa-burger" 
                />
                
                <x-forms.input 
                    type="number"
                    label="Ordem de Exibição" 
                    wire:model="order" 
                    name="order" 
                />
            </div>

            <div class="flex items-center mt-2">
                <button type="button" wire:click="$toggle('is_active')" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $is_active ? 'bg-blue-600' : 'bg-gray-200' }}">
                    <span class="sr-only">Use setting</span>
                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
                <span class="ml-3 text-sm font-medium text-gray-700">{{ $is_active ? 'Categoria Ativa' : 'Categoria Inativa' }}</span>
            </div>

            <div class="mt-5 sm:mt-6 flex justify-end space-x-3 border-t pt-4">
                <button type="button" wire:click="closeModal" class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center">
                    <span wire:loading wire:target="save" class="spinner w-4 h-4 mr-2 border-white border-t-transparent"></span>
                    {{ $isEditing ? 'Salvar Alterações' : 'Criar Categoria' }}
                </button>
            </div>
        </form>
    </x-modal>
</div>
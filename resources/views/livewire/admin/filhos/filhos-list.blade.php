<div>
    <!-- Barra de Filtros -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Busca -->
            <div class="md:col-span-2">
                <x-forms.input 
                    wire:model.live.debounce.500ms="search" 
                    type="text" 
                    placeholder="Buscar por nome, CPF ou email..."
                    icon="search" 
                    
                />
            </div>
            
            <!-- Status -->
            <div>
                <x-forms.select wire:model="status" input-id="filter_status" class="">
                    <option value="">Todos os status</option>
                    <option value="active">Ativo</option>
                    <option value="blocked">Bloqueado</option>
                    <option value="inactive">Inativo</option>
                </x-forms.select>
            </div>
            
            <!-- Per Page -->
            <div>
                <x-forms.select wire:model="perPage" name="paginacao" class="">
                    <option value="10">10 por página</option>
                    <option value="15">15 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                </x-forms.select>
            </div>
        </div>
    </div>
    
    <!-- Ações em Massa -->
    @if(count($selectedFilhos) > 0)
        <div class="bg-primary-50 border border-primary-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-primary-800">
                    {{ count($selectedFilhos) }} filho(s) selecionado(s)
                </span>
                
                <div class="flex space-x-2">
                    <x-button 
                        size="sm" 
                        variant="success"
                        wire:click="bulkAction('activate')">
                        Ativar
                    </x-button>
                    
                    <x-button 
                        size="sm" 
                        variant="danger"
                        wire:click="bulkAction('block')">
                        Bloquear
                    </x-button>
                    
                    <x-button 
                        size="sm" 
                        variant="secondary"
                        wire:click="bulkAction('export')">
                        Exportar
                    </x-button>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input 
                            type="checkbox" 
                            wire:model="selectAll"
                            class="rounded border-gray-300">
                    </th>
                    
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('name')">
                        Nome
                        @if($orderBy === 'name')
                            <x-icon name="{{ $orderDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="inline h-4 w-4" />
                        @endif
                    </th>
                    
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        CPF
                    </th>
                    
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                    </th>
                    
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('status')">
                        Status
                        @if($orderBy === 'status')
                            <x-icon name="{{ $orderDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="inline h-4 w-4" />
                        @endif
                    </th>
                    
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Crédito
                    </th>
                    
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ações
                    </th>
                </tr>
            </thead>
            
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($filhos as $filho)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <input 
                                type="checkbox" 
                                wire:model="selectedFilhos" 
                                value="{{ $filho->id }}"
                                class="rounded border-gray-300">
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($filho->photo_url)
                                    <img src="{{ asset($filho->photo_url) }}" 
                                         alt="{{ $filho->fullname }}" 
                                        class="h-10 w-10 rounded-full">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                                        <x-icon name="user" class="w-8 h-8 text-gray-400" />
                                    </div>
                                @endif
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $filho->fullname }}
                                    </div>
                                </div>
                            </div>

                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $filho->cpf_formatted }}
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $filho->user->email ?? '-' }}
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$filho->status_color">
                                {{ $filho->status_label }}
                            </x-badge>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">
                                    R$ {{ number_format($filho->credit_available, 2, ',', '.') }}
                                </div>
                                <div class="text-gray-500 text-xs">
                                    de R$ {{ number_format($filho->credit_limit, 2, ',', '.') }}
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <x-button 
                                size="sm" 
                                variant="secondary"
                                href="{{ route('admin.filhos.show', $filho) }}">
                                Ver
                            </x-button>
                            
                            <x-button 
                                size="sm" 
                                href="{{ route('admin.filhos.edit', $filho) }}">
                                Editar
                            </x-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <x-icon name="inbox" class="h-12 w-12 mx-auto mb-4 text-gray-400" />
                            <p>Nenhum filho encontrado.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginação -->
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            {{ $filhos->links() }}
        </div>
    </div>
</div>

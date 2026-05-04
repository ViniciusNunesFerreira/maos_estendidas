<div>
    <!-- Cabeçalho e Ações -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">Terminais e Totens (TEF)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Gerencie os totens de autoatendimento e suas maquininhas vinculadas.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar terminal..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
            </div>
            
            <button wire:click="openModal" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Novo Terminal
            </button>
        </div>
    </div>

    <!-- Tabela de Dados -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Identificação (Tablet/App)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pareamento TEF (Maquininha)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Rede / IP</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($devices as $device)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $device->name }}</div>
                                <div class="text-sm text-gray-500 font-mono">{{ $device->internal_id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($device->tef_provider)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 mb-1">
                                        {{ ucfirst($device->tef_provider) }}
                                    </span>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 font-mono truncate" style="max-width: 200px;">{{ $device->tef_device_id ?? 'Sem ID TEF' }}</div>
                                @else
                                    <span class="text-sm text-gray-400 italic">Sem maquininha vinculada</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-300 font-mono">{{ $device->ip_address ?? '--' }}</div>
                                <div class="text-xs text-gray-500">
                                    Ping: {{ $device->last_ping_at ? $device->last_ping_at->diffForHumans() : 'Nunca' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleStatus('{{ $device->id }}')" class="relative inline-flex items-center cursor-pointer transition-colors focus:outline-none">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $device->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $device->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                
                                @if($device->tef_provider === 'mercadopago' && !empty($device->tef_device_id))
                                    <!-- Botão Alternar Modo PDV/Autônomo -->
                                    <button wire:click="toggleOperatingMode('{{ $device->id }}')" title="Forçar Modo PDV na Maquininha" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 transition-colors relative" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="toggleOperatingMode('{{ $device->id }}')">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                        </span>
                                        <span wire:loading wire:target="toggleOperatingMode('{{ $device->id }}')">
                                            <svg class="animate-spin h-5 w-5 inline text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </span>
                                    </button>
                                @endif

                                <!-- Botão Testar Conexão -->
                                <button wire:click="testConnection('{{ $device->id }}')" title="Testar Conexão e Ver Status" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors relative" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="testConnection('{{ $device->id }}')">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                                    </span>
                                    <span wire:loading wire:target="testConnection('{{ $device->id }}')">
                                        <svg class="animate-spin h-5 w-5 inline text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </span>
                                </button>
                                
                                <button wire:click="edit('{{ $device->id }}')" title="Editar" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                
                                <button wire:click="confirmDelete('{{ $device->id }}')" title="Excluir" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                Nenhum terminal cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            {{ $devices->links() }}
        </div>
    </div>

    <!-- Modal de Cadastro/Edição -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <form wire:submit.prevent="save">
                    <div class="px-6 pt-5 pb-4">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                                {{ $isEditMode ? 'Editar Terminal' : 'Novo Terminal' }}
                            </h3>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Fechar</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-5">
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">1. Identificação do Totem (App)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome de Exibição <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="name" placeholder="Ex: Kiosk 01 - Pátio" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ID Interno (No Flutter) <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="internal_id" placeholder="Ex: TOTEM-001" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm uppercase font-mono">
                                        @error('internal_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-3">2. Pareamento com Maquininha (TEF)</h4>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provedor TEF</label>
                                        <select wire:model.live="tef_provider" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            <option value="">Nenhum (Somente PIX/Carteira)</option>
                                            <option value="mercadopago">Mercado Pago Point</option>
                                            <option value="getnet">Getnet</option>
                                            <option value="stone">Stone</option>
                                        </select>
                                        @error('tef_provider') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    @if($tef_provider === 'mercadopago')
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Selecione a Maquininha</label>
                                            <div class="mt-1 flex gap-2">
                                                <select wire:model="tef_device_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">
                                                    <option value="">-- Selecione uma maquininha vinculada --</option>
                                                    @foreach($mpDevices as $mpDevice)
                                                        <option value="{{ $mpDevice['id'] }}">
                                                            ID: {{ $mpDevice['id'] }} (Modo: {{ $mpDevice['operating_mode'] ?? '?' }})
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <button type="button" wire:click="fetchMpDevices" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <span wire:loading.remove wire:target="fetchMpDevices">Buscar</span>
                                                    <span wire:loading wire:target="fetchMpDevices">Buscando...</span>
                                                </button>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500">Clique em "Buscar" para carregar os terminais ativos na sua conta do Mercado Pago.</p>
                                            @error('tef_device_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @else
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ID da Maquininha (Device ID)</label>
                                            <input type="text" wire:model="tef_device_id" placeholder="ID Manual do terminal" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">
                                            @error('tef_device_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">IP de Rede (Opcional)</label>
                                    <input type="text" wire:model="ip_address" placeholder="192.168.1.50" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">
                                    @error('ip_address') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="flex items-center space-x-3 mt-6 cursor-pointer">
                                        <input type="checkbox" wire:model="is_active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Terminal Ativo</span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 flex justify-end gap-3 rounded-b-xl border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Salvar Terminal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
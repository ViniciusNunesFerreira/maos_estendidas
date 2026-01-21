<div>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Maquininhas Cadastradas</h3>
            <p class="mt-1 text-sm text-gray-600">Gerencie os dispositivos Point</p>
        </div>
        <button 
            wire:click="openModal"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Maquininha
        </button>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Device</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Localização</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Última Com.</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($devices as $device)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $device->device_name }}</div>
                            <div class="text-sm text-gray-500">{{ $device->device_id }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $device->location ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $device->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $device->status === 'active' ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $device->last_communication_at?->diffForHumans() ?? 'Nunca' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <button wire:click="openModal({{ $device->id }})" class="text-blue-600 hover:text-blue-900">Editar</button>
                            <button wire:click="toggleStatus({{ $device->id }})" class="ml-3 text-gray-600 hover:text-gray-900">Toggle</button>
                            <button wire:click="delete({{ $device->id }})" class="ml-3 text-red-600 hover:text-red-900">Excluir</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <p class="text-gray-500">Nenhuma maquininha cadastrada</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-show="true" x-cloak>
            <div class="flex min-h-screen items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showModal', false)"></div>
                
                <div class="relative z-50 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold">{{ $editingDevice ? 'Editar' : 'Nova' }} Maquininha</h3>
                    
                    <form wire:submit="save" class="mt-4 space-y-4">
                        @if(!$editingDevice)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Device ID</label>
                                <input type="text" wire:model="device_id" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" wire:model="device_name" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Localização</label>
                            <input type="text" wire:model="location" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <input type="checkbox" wire:model="auto_print" id="auto_print_modal" class="rounded border-gray-300">
                            <label for="auto_print_modal" class="text-sm">Impressão automática</label>
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
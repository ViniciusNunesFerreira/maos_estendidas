<!-- resources/views/livewire/fiscal-settings.blade.php -->
<div>
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Configurações Fiscais</h3>
        
        <form wire:submit="save">
            <!-- SAT -->
            <div class="mb-6">
                <h4 class="text-base font-medium text-gray-900 mb-4">SAT São Paulo</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Número de Série SAT</label>
                        <input type="text" wire:model="satSerialNumber" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código de Ativação</label>
                        <input type="password" wire:model="satActivationCode" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                </div>
            </div>

            <!-- CNPJ -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">CNPJ</label>
                <input type="text" wire:model="cnpj" class="mt-1 block w-full rounded-md border-gray-300">
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Salvar Configurações
            </button>
        </form>
    </div>
</div>

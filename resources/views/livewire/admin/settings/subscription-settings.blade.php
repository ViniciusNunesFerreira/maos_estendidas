<!-- resources/views/livewire/subscription-settings.blade.php -->
<div>
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Configurações de Assinaturas</h3>
        
        <form wire:submit="save">
            <div class="space-y-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="allowSubscriptions" class="rounded border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Permitir assinaturas</span>
                    </label>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Desconto em Assinaturas (%)</label>
                    <input type="number" wire:model="subscriptionDiscount" class="mt-1 block w-full rounded-md border-gray-300" min="0" max="100">
                </div>

                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>
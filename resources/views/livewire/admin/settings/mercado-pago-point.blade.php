<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Configuração Point / TEF</h3>
            <p class="mt-1 text-sm text-gray-600">Configure a integração com maquininhas Point</p>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Device ID</label>
                <input 
                    type="text"
                    wire:model="device_id"
                    placeholder="PAX_A910__SMARTPOS1234567890"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                <p class="mt-1 text-sm text-gray-500">ID do dispositivo Point (obtido via API)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Store ID</label>
                <input 
                    type="text"
                    wire:model="store_id"
                    placeholder="12345678"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">POS ID</label>
                <input 
                    type="text"
                    wire:model="pos_id"
                    placeholder="CAIXA01"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div class="flex items-center gap-3">
                <input 
                    type="checkbox"
                    wire:model="auto_print_receipt"
                    id="auto_print"
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                >
                <label for="auto_print" class="text-sm text-gray-700">
                    Imprimir comprovante automaticamente
                </label>
            </div>

            <div class="flex justify-end pt-4 border-t">
                <button 
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salvar Configurações
                </button>
            </div>

        </form>
    </div>
</div>
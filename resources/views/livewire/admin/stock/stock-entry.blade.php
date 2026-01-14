<div class="bg-white shadow rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Entrada de Estoque</h3>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('message') }}</div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-4">
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700">Buscar Produto</label>
                <input type="text" 
                    wire:model.live.debounce.300ms="searchProduct" 
                    placeholder="Nome, SKU ou código de barras..."
                    class="mt-1 block w-full rounded-md border-gray-300"
                    @if($productId) disabled @endif>
                
                @if(!empty($searchResults))
                    <div class="absolute z-10 w-full bg-white border rounded-md shadow-lg mt-1">
                        @foreach($searchResults as $result)
                            <button type="button" 
                                wire:click="selectProduct('{{ $result['id'] }}')"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 border-b last:border-0">
                                {{ $result['name'] }} <span class="text-xs text-gray-500">({{ $result['sku'] }})</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @if($productId)
                    <button type="button" wire:click="clearProduct" class="text-xs text-red-600 mt-1 underline">Trocar produto</button>
                @endif
                @error('productId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantidade</label>
                    <input type="number" wire:model="quantity" class="mt-1 block w-full rounded-md border-gray-300">
                    @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Custo Unitário</label>
                    <input type="number" step="0.01" wire:model="unit_cost" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Nota Fiscal / Fornecedor</label>
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" wire:model="invoice_number" placeholder="Nº NF" class="mt-1 block w-full rounded-md border-gray-300">
                    <input type="text" wire:model="supplier" placeholder="Fornecedor" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Observações (Motivo)</label>
                <textarea wire:model="reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                @error('reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Registrar Entrada</button>
            </div>
        </div>
    </form>
</div>
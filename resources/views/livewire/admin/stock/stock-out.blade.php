<div class="bg-white shadow rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Saída de Estoque</h3>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-blue-100 text-blue-700 rounded">{{ session('message') }}</div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-4">
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700">Buscar Produto (Em Estoque)</label>
                <input type="text" 
                    wire:model.live.debounce.300ms="searchProduct" 
                    placeholder="Nome, SKU ou código de barras..."
                    class="mt-1 block w-full rounded-md border-gray-300"
                    @if($productId) disabled @endif>
                
                @if(!empty($searchResults))
                    <div class="absolute z-10 w-full bg-white border rounded-md shadow-lg mt-1">
                        @foreach($searchResults as $result)
                            <button type="button" wire:click="selectProduct('{{ $result['id'] }}')"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 border-b">
                                {{ $result['name'] }}  <span class="text-xs text-gray-500"> ( {{ $result['sku'] }} | Disp: {{ $result['stock_quantity'] }} )</span>
                            </button>
                        @endforeach
                    </div>
                @endif
                @if($productId)
                    <p class="text-sm text-blue-600 mt-1">Selecionado: {{ $selectedProduct->name }} (Estoque: {{ $selectedProduct->stock_quantity }})</p>
                @endif
                @error('productId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantidade</label>
                    <input type="number" wire:model.live="quantity" class="mt-1 block w-full rounded-md border-gray-300">
                    @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo de Saída</label>
                    <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="adjustment">Ajuste</option>
                        <option value="out">Venda Manual</option>
                        <option value="return">Devolução</option>
                        <option value="loss">Perda | Vencido | Danificado</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Motivo Detalhado</label>
                <textarea wire:model="reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                @error('reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Registrar Saída</button>
            </div>
        </div>
    </form>
</div>
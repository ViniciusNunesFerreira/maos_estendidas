{{-- resources/views/livewire/admin/products/product-form.blade.php --}}
<div>
    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            {{-- Informações Básicas --}}
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Básicas</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto *</label>
                        <input type="text" wire:model="name" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Refrigerante 350ml">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select wire:model="category_id" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Selecione...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Local de Venda</label>
                        <select wire:model="type" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="ambos">Loja e Cantina</option>
                            <option value="loja">Somente Loja</option>
                            <option value="cantina">Somente Cantina</option>
                        </select>
                        @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <div class="flex">
                            <input type="text" wire:model="sku" readonly class="flex-1 border-gray-300 rounded-l-lg focus:ring-primary-500 focus:border-primary-500" placeholder="Gerado automaticamente">
                            <button type="button" wire:click="generateSku" class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 text-primary">
                                GERAR
                            </button>
                        </div>
                        @error('sku') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras</label>
                        <input type="text" wire:model.blur="barcode" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 7891234567890">
                        @error('barcode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea wire:model="description" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="Descrição opcional do produto..."></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            
            {{-- Preços --}}
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Preços</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço de Venda (R$) *</label>
                        <input type="text" x-data x-on:input="window.maskCurrency($el)" wire:model.lazy="price"  class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="0,00" required>
                        @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço de Custo (R$)</label>
                        <input type="text" x-data x-on:input="window.maskCurrency($el)" wire:model.lazy="cost_price" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                        @error('cost_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

            </div>
            
            {{-- Estoque --}}
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Estoque</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade em Estoque *</label>
                        <input type="number" wire:model="stock_quantity" min="0" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="0">
                        @error('stock_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estoque Mínimo *</label>
                        <input type="number" wire:model="min_stock_alert" min="0" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="5">
                        <p class="text-xs text-gray-500 mt-1">Alerta quando atingir este valor</p>
                        @error('min_stock_alert') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            
            {{-- Imagem --}}
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Imagem</h3>
                
                <div class="flex items-start space-x-6">
                    <div class="flex-shrink-0">
                        @if($image)
                            <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg">
                        @elseif($currentImageUrl)
                            <img src="{{ $currentImageUrl }}" class="w-32 h-32 object-cover rounded-lg">
                        @else
                            <div class="w-32 h-32 bg-gray-100 rounded-lg flex items-center justify-center">
                                <x-icon name="image" class="w-12 h-12 text-gray-300" />
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1">
                        <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <p class="text-xs text-gray-500 mt-2">PNG, JPG ou WEBP. Máximo 2MB.</p>
                        @error('image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        
                        @if($currentImageUrl || $image)
                            <button type="button" wire:click="removeImage" class="mt-2 text-sm text-red-600 hover:text-red-700">
                                Remover imagem
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Status --}}
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Status do Produto</h3>
                        <p class="text-sm text-gray-500">Desative para ocultar das vendas</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">{{ $is_active ? 'Ativo' : 'Inativo' }}</span>
                    </label>
                </div>
            </div>
        </div>
        
        {{-- Ações --}}
        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                <span wire:loading.remove>{{ $isEditing ? 'Salvar Alterações' : 'Criar Produto' }}</span>
                <span wire:loading>Salvando...</span>
            </button>
        </div>
    </form>
</div>
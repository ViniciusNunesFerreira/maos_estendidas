<div>
    <x-modal wire:model="showModal" title="Ajustar Limite de Crédito" name="modal-ajuste-credito" maxWidth="lg">
        <div class="p-6">
            <div class="flex items-center mb-6 p-4 bg-primary-50 rounded-lg border border-primary-100">
                <x-icon name="information-circle" class="h-6 w-6 text-primary-600 mr-3" />
                <p class="text-sm text-primary-700">
                    Alterar o limite impacta diretamente a capacidade de compra de <strong>{{ $filho->fullname }}</strong>.
                </p>
            </div>

            @if($filho)
            <form wire:submit.prevent="save" class="space-y-5" wire:key="form-ajuste-{{ $filho->id }}">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Novo Limite (R$)</label>
                    <div class="relative">

                        <input type="text" x-data x-on:input="window.maskCurrency($el)" wire:model="credit_limit"
                            class="input-focus block w-full py-3 border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            placeholder="0,00" required>
                    </div>
                    @error('credit_limit') <span class="text-danger-600 text-xs mt-1">{{ $message }}</span> @enderror

                   
                    @error('credit_limit') 
                        <span class="text-danger-600 text-xs mt-1 block badge-pulse">{{ $message }}</span> 
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Motivo do Ajuste</label>
                    <textarea wire:model.defer="reason" rows="3"
                        class="input-focus block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        placeholder="Ex: Solicitação do responsável para aumento de gastos escolares..."></textarea>
                    @error('reason') <span class="text-danger-600 text-xs mt-1">{{ $message }}</span> @enderror

                    @error('reason') 
                        <span class="text-danger-600 text-xs mt-1 block badge-pulse">{{ $message }}</span> 
                    @enderror

                </div>

                <div class="flex justify-end space-x-3 mt-8">
                    <x-button type="button" variant="secondary" @click="show = false" wire:loading.attr="disabled">
                        Cancelar
                    </x-button>
                    <x-button type="submit" variant="primary" class="px-8" wire:loading.attr="disabled">
                        <span wire:loading.remove target="save">Confirmar Ajuste</span>
                        <span wire:loading target="save">
                            <div class="spinner w-4 h-4 border-2 mr-2 inline-block"></div> Processando...
                        </span>
                    </x-button>
                </div>
            </form>
            @else
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="spinner border-primary-600"></div>
                    <p class="text-gray-500 mt-4 text-sm">Carregando dados...</p>
                </div>
            @endif
        </div>
    </x-modal>
</div>
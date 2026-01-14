<div>
    <x-modal wire:model="showModal" title="Ajustar Status do Filho" name="modal-status-filho" maxWidth="lg">
        <div class="p-6">
            <div class="flex items-center mb-6 p-4 bg-primary-50 rounded-lg border border-primary-100">
                <x-icon name="information-circle" class="h-6 w-6 text-primary-600 mr-3" />
                <p class="text-sm text-primary-700">
                    Modificar o Status impacta diretamente no acesso e compras de <strong>{{ $filho->fullname }}</strong>.
                </p>
            </div>

            @if($filho)
            <form wire:submit.prevent="save" class="space-y-5" wire:key="form-status-{{ $filho->id }}">
                @if($status !== 'active')
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="text-gray-700 font-medium">
                                Bloqueio por inadimplência?
                            </span>

                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                    wire:model.live="is_blocked_by_debt" 
                                    class="sr-only peer">
                                
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                
                                <span class="ml-3 text-sm font-medium text-gray-900">
                                    {{ $is_blocked_by_debt ? 'Sim' : 'Não' }}
                                </span>
                            </label>
                        </div>
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Motivo do Bloqueio</label>
                        <textarea wire:model.defer="reason" rows="3"
                            class="input-focus block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            placeholder="Ex: Solicitação do responsável para aumento de gastos escolares..."></textarea>
                        
                        @error('reason') 
                            <span class="text-danger-600 text-xs mt-1 block badge-pulse">{{ $message }}</span> 
                        @enderror

                    </div>

                @else

                    
                <div class="flex items-center space-x-3">
                    <span class="text-gray-700 font-medium">
                        Confirma liberar acesso de <strong>{{ $filho->fullname }}</strong> ?
                    </span>
                </div>
                    
            
                @endif

                <div class="flex justify-center space-x-3 mt-8">
                    <x-button type="button" variant="secondary" @click="show = false" wire:loading.attr="disabled">
                        Cancelar
                    </x-button>
                    <x-button type="submit" variant="primary" class="px-8" wire:loading.attr="disabled">
                        <span wire:loading.remove target="save">Confirmar e Liberar</span>
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
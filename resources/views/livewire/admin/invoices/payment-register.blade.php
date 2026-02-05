<div>
    <x-modal  wire:model="showPaymentModal" name="payment-modal" title="Registrar Pagamento" maxWidth="lg">
        <div class="transform transition-all animate-scale-in">
    
            <div class="relative overflow-hidden  bg-gradient-to-r from-primary-50 to-white p-6 border-b border-gray-100">
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        @if($invoice)
                        <h2 class="text-lg font-bold text-gray-900 tracking-tight">{{ $invoice->invoice_number ?? 'FAT-NOVA' }}</h2>
                        
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-400">
                                    {{ now()->format('d/m/Y') }}
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    @if($invoice)
                        <div class="text-right">
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Valor em Aberto</p>
                            <p class="text-2xl font-bold text-primary-700 tracking-tight">
                                R$ {{ number_format($invoice->total_amount - $invoice->paid_amount, 2, ',', '.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            @if($invoice)
            <form wire:submit="save" class="p-6">
                
                {{-- Alerta Informativo (Design Suavizado) --}}
                <div class="flex gap-3 mb-8 p-4 bg-blue-50/50 rounded-lg border border-blue-100/50">
                    <x-icon name="information-circle" class="h-5 w-5 text-primary-600 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-gray-600 leading-relaxed">
                        <span class="font-semibold text-primary-700">Atenção ao Caixa:</span> 
                        Pagamentos menores que o total mudarão o status para <span class="font-medium text-amber-600 bg-amber-50 px-1 rounded">PARCIAL</span>.
                    </div>
                </div>

                {{-- Input de Valor (Hero Input) --}}
                <div class="mb-6 group">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-700">Valor do Pagamento</label>
                       
                        <button type="button" 
                                wire:click="setFullAmount"
                                class="text-xs text-primary-600 hover:text-primary-800 font-medium transition-colors cursor-pointer focus:outline-none">
                            Usar valor total
                        </button>
                    </div>
                    
                    <div class="relative rounded-lg shadow-sm transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
                        
                        <input type="text" 
                            x-data="{ 
                                init() {
                                    this.$watch('amount', value => {
                                        window.maskCurrency($el);
                                    });
                                    window.maskCurrency($el);
                                }
                            }"
                            wire:model="amount" 
                            x-on:input="window.maskCurrency($el)"
                            class="pl-3 py-3 block w-full rounded-lg border-gray-300 text-gray-900 placeholder-gray-300 text-2xl font-semibold focus:ring-0 focus:border-primary-500 transition-colors" 
                            placeholder="0,00"
                        />
                    </div>
                    @error('amount') 
                        <p class="mt-2 text-sm text-danger-500 flex items-center gap-1">
                            <x-icon name="exclamation-circle" class="h-4 w-4" /> {{ $message }}
                        </p> 
                    @enderror
                </div>

                {{-- Grid de Opções para Método de Pagamento e Observações --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    {{-- Método de Pagamento --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pagamento</label>
                        <div class="relative">
                            <select wire:model="paymentMethod" 
                                class="block w-full rounded-lg border-gray-300 py-2.5 pl-3 pr-10 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm shadow-sm">
                                <option value="">Selecione...</option>
                                <option value="pix">💠 PIX</option>
                                <option value="credito">💳 Cartão de Crédito</option>
                                <option value="debito">💳 Cartão de Débito</option>
                                <option value="dinheiro">💵 Dinheiro</option>
                            </select>
                        </div>
                        @error('paymentMethod') <p class="mt-1 text-sm text-danger-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Data ou Referência (Opcional - Layout Grid preenchido) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Referência / NSU</label>
                        <input type="text" 
                            wire:model="reference"
                            placeholder="Ex: Comprovante #123"
                            class="block w-full rounded-lg border-gray-300 py-2.5 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm shadow-sm">
                    </div>
                </div>

                {{-- Observações --}}
                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas Internas <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <textarea wire:model="internal_notes" 
                            rows="2" 
                            class="block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm resize-none shadow-sm transition-shadow"
                            placeholder="Detalhes adicionais sobre esta transação..."></textarea>
                </div>

                {{-- Footer de Ações --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" 
                            wire:click="$set('showPaymentModal', false)" 
                            @click="$dispatch('close-modal', 'payment-modal')"
                            class="px-5 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-all">
                        Cancelar
                    </button>
                    
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="relative px-5 py-2.5 rounded-lg bg-primary-600 text-sm font-semibold text-white shadow-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                        
                        <span wire:loading.remove>Confirmar Pagamento</span>
                        
                        {{-- Loading Spinner State --}}
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processando...
                        </span>
                    </button>
                </div>
            </form>
            
            @else
                {{-- Estado de Carregamento Inicial (Skeleton) --}}
                <div class="p-10 text-center">
                    <div class="spinner mx-auto mb-4 text-primary-500"></div> {{-- Usando classe do admin.css --}}
                    <p class="text-gray-500 text-sm">Buscando fatura...</p>
                </div>
            @endif
        </div>

    </x-modal>
</div>
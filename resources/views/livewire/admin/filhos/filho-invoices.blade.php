<div>
    <!-- Mensagens de Feedback -->
    @if (session()->has('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 border-l-4 border-green-400 animate-fade-in-down">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white shadow sm:rounded-lg">
        <!-- Header com Botão de Ação -->
        <div class="px-6 py-5 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Faturas do Filho</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Histórico financeiro de <strong>{{ $filho->fullname }}</strong>
                    </p>
                </div>
                <div class="flex space-x-3">
                    
                    <!-- BOTÃO CORRIGIDO -->
                    <x-button 
                        variant="primary" 
                        type="button"
                        wire:click="$dispatch('generateInvoice')"
                       
                        class="inline-flex items-center"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Nova Fatura Avulsa
                    </x-button>

                </div>
            </div>

            <!-- Filtros -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
                    <select wire:model.live="filterStatus" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="paid">Pago</option>
                        <option value="overdue">Vencido</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Ordenar</label>
                    <select wire:model.live="sortBy" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="due_date_desc">Vencimento (Recente)</option>
                        <option value="due_date_asc">Vencimento (Antigo)</option>
                        <option value="amount_desc">Maior Valor</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Lista de Faturas -->
        <div class="px-6 py-6">
            @if($invoices->count() > 0)
                <div class="space-y-4">
                    @foreach($invoices as $invoice)
                        <div class="group bg-white border {{ $invoice->status === 'overdue' ? 'border-red-300' : 'border-gray-200' }} rounded-lg p-4 hover:shadow-md transition duration-150 ease-in-out">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4">
                                        <!-- Ícone Status -->
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full 
                                                @if($invoice->status === 'paid') bg-green-100 text-green-600
                                                @elseif($invoice->status === 'overdue') bg-red-100 text-red-600
                                                @else bg-yellow-100 text-yellow-600 @endif
                                                flex items-center justify-center">
                                                @if($invoice->type === 'subscription')
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @else
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <h4 class="text-sm font-bold text-gray-900">
                                                    <a href="{{ route('admin.invoices.show', $invoice->id ) }}" 
                                                    class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                                        {{ $invoice->invoice_number }}
                                                    </a>
                                                    <span class="font-normal text-gray-500">| {{ $invoice->type === 'subscription' ? 'Mensalidade' : 'Consumo' }}</span>
                                                </h4>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium @if($invoice->status === 'paid') bg-green-100 text-green-600 @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($invoice->status) }}
                                                </span>
                                            </div>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                Vencimento: <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900">
                                        R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}
                                    </p>
                                    @if($invoice->status === 'partial')
                                        <span class="items-center px-2 py-0.5 rounded text-sm font-medium bg-green-100 text-green-600">
                                            - R$ {{ $invoice->paid_amount }}
                                        </span>
                                    @endif
                                    @if($invoice->status !== 'paid')
                                        <button wire:click="$dispatch('prepare-payment', { invoiceId: '{{ $invoice->id }}' })"
                                            class="text-sm text-blue-600 hover:text-blue-800 font-medium block mt-2">
                                            Registrar Pagamento
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $invoices->links() }}
                </div>
            @else
                <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <p class="text-gray-500">Nenhuma fatura encontrada.</p>
                </div>
            @endif
        </div>
    </div>

         
    
   
    <!-- MODAL DE GERAÇÃO DE FATURA (CORRIGIDO) -->
    <x-modal wire:key="generate-modal-invoice"  wire:model="showGenerateModal" name="generate-invoice-modal" title="Gerar Nova Fatura" maxWidth="lg">
        <div class="p-6">
            <div class="space-y-5">
                
                <!-- Seleção do Tipo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Fatura</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" wire:click="$set('newInvoiceType', 'subscription')"
                            class="flex flex-col items-center justify-center p-3 border rounded-lg text-sm font-medium transition {{ $newInvoiceType === 'subscription' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                            <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Mensalidade
                        </button>
                        
                        <button type="button" wire:click="$set('newInvoiceType', 'consumption')"
                            class="flex flex-col items-center justify-center p-3 border rounded-lg text-sm font-medium transition {{ $newInvoiceType === 'consumption' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                            <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            Consumo
                        </button>
                        
                        <button type="button" wire:click="$set('newInvoiceType', 'manual')"
                            class="flex flex-col items-center justify-center p-3 border rounded-lg text-sm font-medium transition {{ $newInvoiceType === 'manual' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                            <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Avulso
                        </button>
                    </div>
                </div>

                <!-- Formulário Dinâmico -->
                <div class="bg-gray-50 p-4 rounded-md border border-gray-200 space-y-4">
                    
                    @if($newInvoiceType === 'subscription')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mês de Referência</label>
                            <input type="month" wire:model="newReferenceMonth" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @error('newReferenceMonth') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor</label>
                            <input type="number" step="0.01" wire:model="newAmount" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @error('newAmount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                    @elseif($newInvoiceType === 'consumption')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Início</label>
                                <input type="date" wire:model="newPeriodStart" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                @error('newPeriodStart') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fim</label>
                                <input type="date" wire:model="newPeriodEnd" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                @error('newPeriodEnd') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                    @elseif($newInvoiceType === 'manual')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição</label>
                            <input type="text" wire:model="newDescription" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @error('newDescription') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor</label>
                            <input type="number" step="0.01" wire:model="newAmount" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @error('newAmount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Vencimento -->
                    <div class="pt-2 border-t border-gray-200 mt-2">
                        <label class="block text-sm font-medium text-gray-700">Vencimento</label>
                        <input type="date" wire:model="newDueDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        @error('newDueDate') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button 
                    type="button"
                    wire:click="$set('showGenerateModal', false)" 
                    @click="$dispatch('close-modal', 'generate-invoice-modal')"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button 
                    type="button"
                    wire:click="saveInvoice" 
                    wire:loading.attr="disabled" 
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 flex items-center">
                    <span wire:loading wire:target="saveInvoice" class="mr-2">...</span>
                    Gerar Fatura
                </button>
            </div>
        </div>
    </x-modal>

    <!-- MODAL DE PAGAMENTO -->
    <livewire:admin.invoices.payment-register wire:key="payment-reg-{{ $this->filho->id }}" />
</div>
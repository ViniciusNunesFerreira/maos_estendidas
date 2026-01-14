{{-- resources/views/livewire/admin/filhos/partials/subscription-form.blade.php --}}

<div class="bg-white rounded-lg border border-gray-200 p-6">
    {{-- Header do Formulário --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                {{ $mode === 'create' ? 'Nova Assinatura' : 'Editar Assinatura' }}
            </h3>
            <p class="text-sm text-gray-600 mt-1">
                Para: <span class="font-medium">{{ $filho->user->name ?? $filho->full_name ?? 'N/A' }}</span>
            </p>
        </div>
        
        <button 
            wire:click.prevent="cancelForm"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors text-sm"
        >
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar
        </button>
    </div>

    {{-- Formulário --}}
    <form wire:submit.prevent="saveSubscription">
        <div class="space-y-5">
            {{-- Nome do Plano --}}
            <div>
                <label for="plan_name" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Nome do Plano <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="plan_name"
                    wire:model="formData.plan_name"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('formData.plan_name') border-red-500 @enderror"
                    placeholder="Ex: Mensalidade Casa Lar"
                >
                @error('formData.plan_name') 
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Valor Mensal --}}
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Valor Mensal (R$) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-500 text-sm">R$</span>
                    <input 
                        type="number" 
                        id="amount"
                        step="0.01"
                        min="0"
                        wire:model="formData.amount"
                        class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('formData.amount') border-red-500 @enderror"
                        placeholder="350.00"
                    >
                </div>
                @error('formData.amount') 
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Grid: Ciclo e Dia --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Ciclo de Cobrança --}}
                <div>
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Ciclo de Cobrança <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="billing_cycle"
                        wire:model="formData.billing_cycle"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('formData.billing_cycle') border-red-500 @enderror"
                    >
                        <option value="monthly">Mensal</option>
                        <option value="quarterly">Trimestral</option>
                        <option value="yearly">Anual</option>
                    </select>
                    @error('formData.billing_cycle') 
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Dia de Cobrança --}}
                <div>
                    <label for="billing_day" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Dia de Cobrança <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="billing_day"
                        min="1"
                        max="28"
                        wire:model="formData.billing_day"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('formData.billing_day') border-red-500 @enderror"
                        placeholder="10"
                    >
                    <p class="text-gray-500 text-xs mt-1">Escolha um dia entre 1 e 28</p>
                    @error('formData.billing_day') 
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p> 
                    @enderror
                </div>
            </div>

            {{-- Data de Início --}}
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Data de Início
                </label>
                <input 
                    type="date" 
                    id="start_date"
                    wire:model="formData.start_date"
                    min="{{ now()->format('Y-m-d') }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('formData.start_date') border-red-500 @enderror"
                >
                <p class="text-gray-500 text-xs mt-1">Deixe vazio para iniciar hoje</p>
                @error('formData.start_date') 
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Preview dos Valores --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-900 mb-2.5">Resumo da Assinatura</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-blue-700">Plano:</span>
                        <span class="text-blue-900 font-medium ml-2">{{ $formData['plan_name'] ?: 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Valor:</span>
                        <span class="text-blue-900 font-bold ml-2">R$ {{ number_format($formData['amount'], 2, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Ciclo:</span>
                        <span class="text-blue-900 font-medium ml-2">
                            @switch($formData['billing_cycle'])
                                @case('monthly') Mensal @break
                                @case('quarterly') Trimestral @break
                                @case('yearly') Anual @break
                                @default {{ ucfirst($formData['billing_cycle']) }}
                            @endswitch
                        </span>
                    </div>
                    <div>
                        <span class="text-blue-700">Dia de Cobrança:</span>
                        <span class="text-blue-900 font-medium ml-2">Todo dia {{ $formData['billing_day'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Botões do Formulário --}}
        <div class="mt-6 pt-5 border-t border-gray-200 flex items-center justify-between">
            <button 
                type="button"
                wire:click.prevent="cancelForm"
                class="inline-flex items-center px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors font-medium text-sm"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar
            </button>

            <button 
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed text-sm"
            >
                <svg wire:loading.remove class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg wire:loading class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove>{{ $mode === 'create' ? 'Criar Assinatura' : 'Salvar Alterações' }}</span>
                <span wire:loading>Salvando...</span>
            </button>
        </div>
    </form>

    {{-- Informações Adicionais --}}
    <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center">
            <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Informações Importantes
        </h4>
        <ul class="space-y-1.5 text-xs text-gray-600">
            <li class="flex items-start">
                <svg class="w-3 h-3 mr-1.5 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                A primeira fatura será gerada 30 dias após a data de início
            </li>
            <li class="flex items-start">
                <svg class="w-3 h-3 mr-1.5 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                O dia de cobrança deve estar entre 1 e 28 para evitar problemas
            </li>
            <li class="flex items-start">
                <svg class="w-3 h-3 mr-1.5 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Assinaturas criadas começam automaticamente com status "Ativa"
            </li>
        </ul>
    </div>
</div>
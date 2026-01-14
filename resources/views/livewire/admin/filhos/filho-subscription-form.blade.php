{{-- resources/views/livewire/admin/filhos/filho-subscription-form.blade.php --}}
<div class="max-w-4xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ $isEditing ? 'Editar Assinatura' : 'Nova Assinatura' }}
                </h1>
                <p class="text-gray-600 mt-2">
                    Para: <span class="font-medium">{{ $filho->user->name ?? $filho->full_name ?? 'N/A' }}</span>
                </p>
            </div>
            
            <button 
                wire:click="cancel"
                type="button"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-green-800 font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-red-800 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Formulário --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form wire:submit.prevent="save">
            <div class="p-8 space-y-6">
                {{-- Nome do Plano --}}
                <div>
                    <label for="plan_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Plano *
                    </label>
                    <input 
                        type="text" 
                        id="plan_name"
                        wire:model="plan_name"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors @error('plan_name') border-red-500 @enderror"
                        placeholder="Ex: Mensalidade Casa Lar"
                    >
                    @error('plan_name') 
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Valor Mensal --}}
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Valor Mensal (R$) *
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-gray-500">R$</span>
                        <input 
                            type="number" 
                            id="amount"
                            step="0.01"
                            min="0"
                            wire:model="amount"
                            class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors @error('amount') border-red-500 @enderror"
                            placeholder="350.00"
                        >
                    </div>
                    @error('amount') 
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Grid: Ciclo e Dia --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Ciclo de Cobrança --}}
                    <div>
                        <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-2">
                            Ciclo de Cobrança *
                        </label>
                        <select 
                            id="billing_cycle"
                            wire:model="billing_cycle"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors @error('billing_cycle') border-red-500 @enderror"
                        >
                            <option value="monthly">Mensal</option>
                            <option value="quarterly">Trimestral</option>
                            <option value="yearly">Anual</option>
                        </select>
                        @error('billing_cycle') 
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p> 
                        @enderror
                    </div>

                    {{-- Dia de Cobrança --}}
                    <div>
                        <label for="billing_day" class="block text-sm font-medium text-gray-700 mb-2">
                            Dia de Cobrança *
                        </label>
                        <input 
                            type="number" 
                            id="billing_day"
                            min="1"
                            max="28"
                            wire:model="billing_day"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors @error('billing_day') border-red-500 @enderror"
                            placeholder="10"
                        >
                        <p class="text-gray-500 text-xs mt-1">Escolha um dia entre 1 e 28</p>
                        @error('billing_day') 
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p> 
                        @enderror
                    </div>
                </div>

                {{-- Data de Início --}}
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Data de Início
                    </label>
                    <input 
                        type="date" 
                        id="start_date"
                        wire:model="start_date"
                        min="{{ now()->format('Y-m-d') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors @error('start_date') border-red-500 @enderror"
                    >
                    <p class="text-gray-500 text-xs mt-1">Deixe vazio para iniciar hoje</p>
                    @error('start_date') 
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Preview dos Valores --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-blue-900 mb-3">Resumo da Assinatura</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-blue-700">Plano:</span>
                            <span class="text-blue-900 font-medium ml-2">{{ $plan_name ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-blue-700">Valor:</span>
                            <span class="text-blue-900 font-bold ml-2">R$ {{ number_format($amount, 2, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-blue-700">Ciclo:</span>
                            <span class="text-blue-900 font-medium ml-2">
                                @switch($billing_cycle)
                                    @case('monthly') Mensal @break
                                    @case('quarterly') Trimestral @break
                                    @case('yearly') Anual @break
                                    @default {{ ucfirst($billing_cycle) }}
                                @endswitch
                            </span>
                        </div>
                        <div>
                            <span class="text-blue-700">Dia de Cobrança:</span>
                            <span class="text-blue-900 font-medium ml-2">Todo dia {{ $billing_day }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer com Botões --}}
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex items-center justify-between rounded-b-lg">
                <button 
                    type="button"
                    wire:click="cancel"
                    class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors font-medium"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancelar
                </button>

                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg wire:loading.remove class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg wire:loading class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove>{{ $isEditing ? 'Salvar Alterações' : 'Criar Assinatura' }}</span>
                    <span wire:loading>Salvando...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Informações Adicionais --}}
    <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Informações Importantes
        </h3>
        <ul class="space-y-2 text-sm text-gray-600">
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                A primeira fatura será gerada 30 dias após a data de início da assinatura
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                O dia de cobrança deve estar entre 1 e 28 para evitar problemas em meses com menos dias
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Assinaturas criadas começam automaticamente com status "Ativa"
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Você poderá pausar ou cancelar a assinatura a qualquer momento
            </li>
        </ul>
    </div>
</div>
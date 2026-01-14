<div class="mb-6 flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-900">
        @if($subscription)
            Assinatura Ativa
        @else
            Gerenciar Assinatura
        @endif
    </h3>
    
    @if($subscription && $subscription->status === 'active')
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Detalhes
            </a>
                 
            <button 
                wire:click.prevent="$set('showPauseConfirm', true)"
                type="button"
                class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm font-medium hover:bg-yellow-700 transition-colors"
            >

                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Pausar
        
            </button>
            
            <button 
                wire:click.prevent="$set('showCancelConfirm', true)"
                type="button"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar
            </button>

        </div>
    @elseif($subscription && $subscription->status === 'paused')
        <button 
            wire:click.prevent="$set('showResumeConfirm', true)"
            type="button"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Retomar
        </button>
    @else
        <a href="{{ route('admin.subscriptions.create', ['filho_id' => $this->filho->id ]) }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Criar Assinatura
        </a>
    @endif
</div>

{{-- Confirmações Inline --}}
@if($showPauseConfirm)
    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1">
                <h4 class="text-yellow-800 font-semibold mb-2">Pausar Assinatura</h4>
                <p class="text-yellow-700 text-sm mb-3">Você poderá retomar a qualquer momento.</p>
                
                <form action="{{ route('admin.subscriptions.pause', $subscription) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-yellow-800 mb-1">Motivo (opcional)</label>
                        <textarea 
                           name="reason"
                            rows="2"
                            class="w-full px-3 py-2 border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm"
                        ></textarea>
                    </div>
                    
                    <div class="flex gap-2">
                        <button  type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-medium text-sm">
                            Confirmar
                        </button>
                        <button 
                            wire:click.prevent="$set('showPauseConfirm', false)"
                            type="button"
                            class="px-4 py-2 border border-yellow-600 text-yellow-700 rounded-lg hover:bg-yellow-50 font-medium text-sm"
                        >
                            Cancelar
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endif

@if($showResumeConfirm)
    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <h4 class="text-green-800 font-semibold mb-2">Retomar Assinatura</h4>
                <p class="text-green-700 text-sm mb-3">As cobranças serão reativadas.</p>

                 <form action="{{ route('admin.subscriptions.resume', $subscription->id) }}" method="POST">
                    @csrf
                        
                    <div class="flex gap-2">
                        <button 
                           
                            type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm"
                        >
                            Confirmar
                        </button>
                        <button 
                            wire:click.prevent="$set('showResumeConfirm', false)"
                            type="button"
                            class="px-4 py-2 border border-green-600 text-green-700 rounded-lg hover:bg-green-50 font-medium text-sm"
                        >
                            Cancelar
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endif

@if($showCancelConfirm)
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-red-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1">
                <h4 class="text-red-800 font-semibold mb-2">Cancelar Assinatura</h4>
                <p class="text-red-700 text-sm mb-3"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-red-800 mb-1">Motivo do Cancelamento *</label>
                    <textarea 
                        wire:model="cancellationReason"
                        rows="3"
                        class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                        placeholder="Mínimo 10 caracteres"
                    ></textarea>
                    <p class="text-red-600 text-xs mt-1">Obrigatório: mínimo 10 caracteres</p>
                </div>
                
                <div class="flex gap-2">
                    <button 
                        wire:click.prevent="cancelSubscription"
                        type="button"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm"
                    >
                        Confirmar Cancelamento
                    </button>
                    <button 
                        wire:click.prevent="$set('showCancelConfirm', false)"
                        type="button"
                        class="px-4 py-2 border border-red-600 text-red-700 rounded-lg hover:bg-red-50 font-medium text-sm"
                    >
                        Voltar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Conteúdo da Assinatura --}}
@if($subscription)
    {{-- Cards de Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ $this->stats['total_invoices'] }}</span>
            </div>
            <p class="text-sm font-medium text-gray-600">Total de Faturas</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ $this->stats['paid_invoices'] }}</span>
            </div>
            <p class="text-sm font-medium text-gray-600">Faturas Pagas</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $this->stats['payment_rate'] }}% adimplência</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ $this->stats['overdue_invoices'] }}</span>
            </div>
            <p class="text-sm font-medium text-gray-600">Faturas Vencidas</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-900">R$ {{ number_format($this->stats['total_paid'], 2, ',', '.') }}</span>
            </div>
            <p class="text-sm font-medium text-gray-600">Total Pago</p>
            <p class="text-xs text-red-500 mt-0.5">R$ {{ number_format($this->stats['total_pending'], 2, ',', '.') }} pendente</p>
        </div>
    </div>

    {{-- Detalhes da Assinatura --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h4 class="text-base font-semibold text-gray-900 mb-4">Detalhes da Assinatura</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-medium text-gray-500 block mb-1">Status</label>
                @php
                    $statusConfig = [
                        'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Ativa'],
                        'paused' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Pausada'],
                        'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Cancelada'],
                    ];
                    $config = $statusConfig[$subscription->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => ucfirst($subscription->status)];
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 {{ $config['bg'] }} {{ $config['text'] }} rounded-full text-xs font-medium">
                    {{ $config['label'] }}
                </span>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 block mb-1">Plano</label>
                <p class="text-sm text-gray-900 font-medium">{{ $subscription->plan_name }}</p>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 block mb-1">Valor Mensal</label>
                <p class="text-sm text-gray-900 font-bold">R$ {{ number_format($subscription->amount, 2, ',', '.') }}</p>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 block mb-1">Ciclo</label>
                <p class="text-sm text-gray-900 font-medium">
                    @switch($subscription->billing_cycle)
                        @case('monthly') Mensal @break
                        @case('quarterly') Trimestral @break
                        @case('yearly') Anual @break
                        @default {{ ucfirst($subscription->billing_cycle) }}
                    @endswitch
                </p>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 block mb-1">Dia de Cobrança</label>
                <p class="text-sm text-gray-900 font-medium">Dia {{ $subscription->billing_day }}</p>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 block mb-1">Próxima Cobrança</label>
                <p class="text-sm text-gray-900 font-medium">
                    {{ $subscription->next_billing_date ? $subscription->next_billing_date->format('d/m/Y') : 'N/A' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Histórico de Faturas --}}
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h4 class="text-base font-semibold text-gray-900">Histórico de Faturas</h4>
            
            <div class="flex gap-1">
                <button 
                    wire:click.prevent="$set('invoiceFilter', 'all')"
                    class="px-2.5 py-1 rounded text-xs font-medium {{ $invoiceFilter === 'all' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    Todas
                </button>
                <button 
                    wire:click.prevent="$set('invoiceFilter', 'pending')"
                    class="px-2.5 py-1 rounded text-xs font-medium {{ $invoiceFilter === 'pending' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    Pendentes
                </button>
                <button 
                    wire:click.prevent="$set('invoiceFilter', 'paid')"
                    class="px-2.5 py-1 rounded text-xs font-medium {{ $invoiceFilter === 'paid' ? 'bg-green-100 text-green-700' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    Pagas
                </button>
                <button 
                    wire:click.prevent="$set('invoiceFilter', 'overdue')"
                    class="px-2.5 py-1 rounded text-xs font-medium {{ $invoiceFilter === 'overdue' ? 'bg-red-100 text-red-700' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    Vencidas
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Número</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Período</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Vencimento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Valor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Pago em</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-xs font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $invoice->period_start->format('d/m/Y') }} - {{ $invoice->period_end->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-xs text-gray-900">{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-xs font-bold text-gray-900">R$ {{ number_format($invoice->total, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $invoiceStatus = [
                                        'open' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Aberta'],
                                        'paid' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Paga'],
                                        'overdue' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Vencida'],
                                    ];
                                    $statusInfo = $invoiceStatus[$invoice->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => ucfirst($invoice->status)];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 {{ $statusInfo['bg'] }} {{ $statusInfo['text'] }} rounded-full text-xs font-medium">
                                    {{ $statusInfo['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-2 text-xs text-gray-500">Nenhuma fatura encontrada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 mb-1">Nenhuma assinatura ativa</h3>
        <p class="text-sm text-gray-500 mb-4">Este filho ainda não possui uma assinatura cadastrada.</p>
        <a  href="{{ route('admin.subscriptions.create', ['filho_id' => $this->filho->id]) }}"class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors text-sm" >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Criar Primeira Assinatura
        </a>
    </div>
@endif
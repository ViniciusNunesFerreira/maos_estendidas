<x-layouts.admin>

<x-slot name="title">Detalhes da Assinatura</x-slot>

<div class="space-y-6">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Detalhes da Assinatura</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $subscription->filho->user->name ?? $subscription->filho->name ?? 'N/A' }}
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.subscriptions.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>

                @if($subscription->status === 'active')
                    <a href="{{ route('admin.subscriptions.edit', $subscription->id) }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-500">Total de Faturas</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_invoices'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-500">Faturas Pagas <small>(Total|Parcial)</small></p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['paid_invoices'] }}</p>
                    <p class="text-xs text-gray-500">{{ $stats['payment_rate'] }}% adimplência</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-500">Total Pago</p>
                    <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($stats['total_paid'], 2, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-500">Próxima Cobrança</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ $subscription->next_billing_date ? $subscription->next_billing_date->format('d/m/Y') : '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Informações da Assinatura --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Card Principal --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Informações da Assinatura</h2>
                    
                    @php
                        $statusConfig = [
                            'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Ativa'],
                            'paused' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Pausada'],
                            'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Cancelada'],
                        ];
                        $config = $statusConfig[$subscription->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => ucfirst($subscription->status)];
                    @endphp
                    <span class="inline-flex px-3 py-1 text-sm font-semibold {{ $config['bg'] }} {{ $config['text'] }} rounded-full">
                        {{ $config['label'] }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Plano</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $subscription->plan_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Valor Mensal</dt>
                        <dd class="mt-1 text-base font-bold text-gray-900">R$ {{ number_format($subscription->amount, 2, ',', '.') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ciclo de Cobrança</dt>
                        <dd class="mt-1 text-base text-gray-900">
                            @switch($subscription->billing_cycle)
                                @case('monthly') Mensal @break
                                @case('quarterly') Trimestral @break
                                @case('yearly') Anual @break
                                @default {{ ucfirst($subscription->billing_cycle) }}
                            @endswitch
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Dia de Cobrança</dt>
                        <dd class="mt-1 text-base text-gray-900">Todo dia {{ $subscription->billing_day }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Data de Início</dt>
                        <dd class="mt-1 text-base text-gray-900">{{ $subscription->started_at->format('d/m/Y') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Primeira Cobrança</dt>
                        <dd class="mt-1 text-base text-gray-900">{{ $subscription->first_billing_date->format('d/m/Y') }}</dd>
                    </div>

                    @if($subscription->plan_description)
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $subscription->plan_description }}</dd>
                        </div>
                    @endif

                    @if($subscription->status_reason)
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Motivo</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $subscription->status_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Histórico de Faturas --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Histórico de Faturas</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pago em</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($subscription->invoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $invoice->invoice_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $invoice->period_start->format('d/m/Y') }} - {{ $invoice->period_end->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $invoice->due_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        R$ {{ number_format($invoice->paid_amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $invoiceStatus = [
                                                'pending' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Aberta'],
                                                'partial' => ['bg' => 'bg-yellow-100', 'text' => 'text-blue-800', 'label' => 'Paga Parcial'],
                                                'paid' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Paga'],
                                                'overdue' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Vencida'],
                                            ];
                                            $invoiceConfig = $invoiceStatus[$invoice->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => ucfirst($invoice->status)];
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold {{ $invoiceConfig['bg'] }} {{ $invoiceConfig['text'] }} rounded-full">
                                            {{ $invoiceConfig['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                        Nenhuma fatura gerada ainda
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar de Ações --}}
        <div class="space-y-6">
            {{-- Dados do Filho --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Dados do Filho</h3>
                
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 h-12 w-12">
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-semibold">
                                {{ strtoupper(substr($subscription->filho->user->name ?? $subscription->filho->name ?? 'N/A', 0, 2)) }}
                            </span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $subscription->filho->user->name ?? $subscription->filho->name ?? 'N/A' }}
                        </p>
                        <p class="text-xs text-gray-500">
                            CPF: {{ $subscription->filho->cpf ? substr($subscription->filho->cpf, 0, 3) . '.***.***-' . substr($subscription->filho->cpf, -2) : 'N/A' }}
                        </p>
                    </div>
                </div>

                <a href="{{ route('admin.filhos.show', $subscription->filho->id) }}" 
                   class="block w-full text-center px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    Ver Perfil Completo
                </a>
            </div>

            {{-- Ações --}}
            @if($subscription->status === 'active')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Ações Rápidas</h3>
                    
                    <div class="space-y-3">
                        
                        <button onclick="document.getElementById('pauseModal').classList.remove('hidden')" 
                                class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm font-medium">
                            Pausar Assinatura
                        </button>

                        <button onclick="document.getElementById('cancelModal').classList.remove('hidden')" 
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                            Cancelar Assinatura
                        </button>
                    </div>
                </div>
            @elseif($subscription->status === 'paused')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Ações Rápidas</h3>
                    
                    <form action="{{ route('admin.subscriptions.resume', $subscription->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                            Retomar Assinatura
                        </button>
                    </form>
                </div>
            @endif

            {{-- Informações Adicionais --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Informações</h3>
                
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Criado em</dt>
                        <dd class="text-gray-900 font-medium">{{ $subscription->created_at->format('d/m/Y H:i') }}</dd>
                    </div>

                    @if($subscription->approvedBy)
                        <div>
                            <dt class="text-gray-500">Aprovado por</dt>
                            <dd class="text-gray-900 font-medium">{{ $subscription->approvedBy->name }}</dd>
                        </div>
                    @endif

                    @if($subscription->paused_at)
                        <div>
                            <dt class="text-gray-500">Pausada em</dt>
                            <dd class="text-gray-900 font-medium">{{ $subscription->paused_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif

                    @if($subscription->cancelled_at)
                        <div>
                            <dt class="text-gray-500">Cancelada em</dt>
                            <dd class="text-gray-900 font-medium">{{ $subscription->cancelled_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- Modal Pausar --}}
<div id="pauseModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Pausar Assinatura</h3>
            
            <form action="{{ route('admin.subscriptions.pause', $subscription) }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motivo (opcional)</label>
                    <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                        Confirmar
                    </button>
                    <button type="button" onclick="document.getElementById('pauseModal').classList.add('hidden')" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Cancelar --}}
<div id="cancelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancelar Assinatura</h3>
            
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-800">
                    <strong>Atenção:</strong> Esta ação não pode ser desfeita.
                </p>
            </div>
            
            <form action="{{ route('admin.subscriptions.cancel', $subscription->id) }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motivo do Cancelamento *</label>
                    <textarea name="reason" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Mínimo 10 caracteres"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Confirmar Cancelamento
                    </button>
                    <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Voltar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.admin>
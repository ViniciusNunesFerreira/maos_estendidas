<div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-blue-600 to-blue-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-20 w-20 rounded-full bg-white flex items-center justify-center">
                            <svg class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-white">
                        <h3 class="text-2xl font-bold">{{ $filho->fullname }}</h3>
                        <p class="text-blue-100">CPF: {{ $this->formatCpf($filho->cpf) }}</p>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $filho->status === 'active' ? 'bg-green-400 text-green-900' : 'bg-red-400 text-red-900' }}">
                                {{ ucfirst($filho->status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button wire:click="editFilho" class="inline-flex items-center px-4 py-2 border border-white text-sm font-medium rounded-md text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </button>
                    @if($filho->status === 'active')
                        <button wire:click="blockFilho" wire:confirm="Tem certeza que deseja bloquear este filho?" class="inline-flex items-center px-4 py-2 border border-white text-sm font-medium rounded-md text-white hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Bloquear
                        </button>
                    @else
                        <button wire:click="unblockFilho" class="inline-flex items-center px-4 py-2 border border-white text-sm font-medium rounded-md text-white hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Desbloquear
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Grid de Informações -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-6 bg-gray-50">
            <!-- Crédito Disponível -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Crédito Disponível</p>
                        <p class="mt-1 text-2xl font-bold text-green-600">
                            R$ {{ number_format($filho->available_credit, 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $this->getCreditPercentage() }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Limite: R$ {{ number_format($filho->credit_limit, 2, ',', '.') }}</p>
                </div>
            </div>

            <!-- Crédito Utilizado -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Crédito Utilizado</p>
                        <p class="mt-1 text-2xl font-bold text-orange-600">
                            R$ {{ number_format($filho->credit_used, 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-full">
                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total de Pedidos -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total de Pedidos</p>
                        <p class="mt-1 text-2xl font-bold text-blue-600">{{ $totalOrders }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Este mês: {{ $ordersThisMonth }}</p>
            </div>

            <!-- Faturas Pendentes -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Faturas Pendentes</p>
                        <p class="mt-1 text-2xl font-bold {{ $pendingInvoices > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $pendingInvoices }}
                        </p>
                    </div>
                    <div class="p-3 {{ $pendingInvoices > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-full">
                        <svg class="h-6 w-6 {{ $pendingInvoices > 0 ? 'text-red-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                @if($pendingInvoices > 0)
                    <p class="text-xs text-red-500 mt-2">Ação necessária!</p>
                @endif
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 px-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button wire:click="$set('activeTab', 'info')" class="@if($activeTab === 'info') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Informações
                </button>
                <button wire:click="$set('activeTab', 'credit')" class="@if($activeTab === 'credit') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Histórico de Crédito
                </button>
                <button wire:click="$set('activeTab', 'orders')" class="@if($activeTab === 'orders') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Últimos Pedidos
                </button>
                <button wire:click="$set('activeTab', 'invoices')" class="@if($activeTab === 'invoices') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Faturas
                </button>
                <button wire:click="$set('activeTab', 'subscriptions')" class="@if($activeTab === 'subscriptions') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Assinaturas
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="px-6 py-6">
            @if($activeTab === 'info')
                <!-- Informações Pessoais -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Dados Pessoais</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $filho->email ?? 'Não informado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Telefone</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $filho->phone ?? 'Não informado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Data de Nascimento</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $filho->birth_date ? $filho->birth_date->format('d/m/Y') : 'Não informada' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Data de Cadastro</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $filho->created_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Informações de Crédito</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Limite de Crédito</dt>
                                <dd class="mt-1 text-sm text-gray-900">R$ {{ number_format($filho->credit_limit, 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Crédito Disponível</dt>
                                <dd class="mt-1 text-sm text-green-600 font-medium">R$ {{ number_format($filho->available_credit, 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Crédito Utilizado</dt>
                                <dd class="mt-1 text-sm text-orange-600 font-medium">R$ {{ number_format($filho->used_credit, 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dia de Vencimento</dt>
                                <dd class="mt-1 text-sm text-gray-900">Dia {{ $filho->due_day ?? 'Não definido' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                @if($filho->notes)
                    <div class="mt-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Observações</h4>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-gray-700">{{ $filho->notes }}</p>
                        </div>
                    </div>
                @endif

            @elseif($activeTab === 'credit')
                <!-- Histórico de Crédito -->
                <div class="space-y-4">
                    @forelse($creditHistory as $credit)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full {{ $credit->type === 'credit' ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center">
                                            <svg class="h-5 w-5 {{ $credit->type === 'credit' ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($credit->type === 'credit')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                                @endif
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $credit->description }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $credit->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold {{ $credit->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $credit->type === 'credit' ? '+' : '-' }} R$ {{ number_format($credit->amount, 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Nenhum histórico de crédito encontrado</p>
                        </div>
                    @endforelse
                </div>

            @elseif($activeTab === 'orders')
                <!-- Últimos Pedidos -->
                <div class="space-y-4">
                    @forelse($recentOrders as $order)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-gray-900">#{{ $order->order_number }}</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($order->status === 'completed') bg-green-100 text-green-800
                                            @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $order->created_at->format('d/m/Y H:i') }} - {{ $order->items_count }} itens
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900">
                                        R$ {{ number_format($order->total_amount, 2, ',', '.') }}
                                    </p>
                                    <button wire:click="viewOrder('{{ $order->id }}')" class="text-xs text-blue-600 hover:text-blue-800">
                                        Ver detalhes →
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Nenhum pedido encontrado</p>
                        </div>
                    @endforelse
                </div>

            @elseif($activeTab === 'invoices')
                <!-- Faturas -->
                <div class="space-y-4">
                    @forelse($invoices as $invoice)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-gray-900">
                                            Fatura {{ $invoice->reference_month }}/{{ $invoice->reference_year }}
                                        </p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($invoice->status === 'paid') bg-green-100 text-green-800
                                            @elseif($invoice->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Vencimento: {{ $invoice->due_date->format('d/m/Y') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900">
                                        R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}
                                    </p>
                                    <button wire:click="viewInvoice('{{ $invoice->id }}')" class="text-xs text-blue-600 hover:text-blue-800">
                                        Ver detalhes →
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Nenhuma fatura encontrada</p>
                        </div>
                    @endforelse
                </div>

            @elseif($activeTab === 'subscriptions')
                <!-- Assinaturas -->
                <div class="space-y-4">
                    @forelse($subscriptions as $subscription)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <img src="{{ $subscription->product->image_url ?? '/images/placeholder.png' }}" alt="{{ $subscription->product->name }}" class="h-16 w-16 rounded object-cover">
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $subscription->product->name }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ ucfirst($subscription->frequency) }} - 
                                            Próxima cobrança: {{ $subscription->next_billing_date->format('d/m/Y') }}
                                        </p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1
                                            {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $subscription->status === 'active' ? 'Ativa' : 'Inativa' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900">
                                        R$ {{ number_format($subscription->amount, 2, ',', '.') }}
                                    </p>
                                    @if($subscription->status === 'active')
                                        <button wire:click="cancelSubscription('{{ $subscription->id }}')" wire:confirm="Tem certeza que deseja cancelar esta assinatura?" class="text-xs text-red-600 hover:text-red-800">
                                            Cancelar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Nenhuma assinatura encontrada</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
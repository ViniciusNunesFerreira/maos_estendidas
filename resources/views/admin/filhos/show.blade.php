<x-layouts.admin title="{{ $filho->name }}">
    <div class="space-y-6">
        <!-- Header com Ações -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">

                @if($filho->photo_url)
                    <img src="{{ asset($filho->photo_url) }}" 
                            alt="{{ $filho->fullname }}" 
                        class="h-16 w-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center">
                        <x-icon name="user" class="w-10 h-10 text-gray-400" />
                    </div>
                @endif
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $filho->fullname }}</h1>
                    <div class="flex items-center space-x-3 mt-1">
                        <x-badge :color="$filho->status_color">
                            {{ $filho->status_label }}
                        </x-badge>
                        <span class="text-sm text-gray-600">
                            CPF: {{ $filho->cpf_formatted }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-3">
                <x-button 
                    variant="secondary"
                    href="{{ route('admin.filhos.edit', $filho) }}">
                    <x-icon name="pencil" class="h-5 w-5 mr-2" />
                    Editar
                </x-button>
                
                @if($filho->status === 'active')
                    <x-button 
                        variant="danger"
                        x-data 
                        @click="$dispatch('open-modal', 'modal-status-filho'); Livewire.dispatch('adjustStatusFilho', { filho: '{{ $filho->id }}', status: 'suspended'} );">
                        <x-icon name="lock-closed" class="h-5 w-5 mr-2" />
                        Bloquear
                    </x-button>
                @elseif($filho->status === 'suspended')
                    <x-button 
                        variant="success"
                        x-data 
                        @click="$dispatch('open-modal', 'modal-status-filho'); Livewire.dispatch('adjustStatusFilho', { filho: '{{ $filho->id }}', status: 'active' });">
                        <x-icon name="lock-open" class="h-5 w-5 mr-2" />
                        Ativar
                    </x-button>
                @endif
            </div>
        </div>
        
        <!-- Grid de Informações -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Crédito Disponível -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Crédito Disponível</h3>
                    <x-icon name="credit-card" class="h-5 w-5 text-primary-500" />
                </div>
                
                <div class="space-y-2">
                    <div>
                        <p class="text-3xl font-bold text-gray-900">
                            R$ {{ number_format($filho->credit_available, 2, ',', '.') }}
                        </p>
                        <p class="text-sm text-gray-500">
                            de R$ {{ number_format($filho->credit_limit, 2, ',', '.') }}
                        </p>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary-600 h-2 rounded-full" 
                             style="width: {{  ($filho->credit_limit != 0) ? ($filho->credit_used / $filho->credit_limit) * 100 : 0 }}%">
                        </div>
                    </div>
                    
                    <button class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                        x-data 
                        @click="$dispatch('open-modal', 'modal-ajuste-credito'); Livewire.dispatch('adjustCredit', { filho: '{{ $filho->id }}' });">
                        Ajustar Crédito →
                    </button>

                </div>
            </div>
            
            <!-- Total Consumido -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Total Consumido (Mês)</h3>
                    <x-icon name="trending-up" class="h-5 w-5 text-success-500" />
                </div>
                
                <p class="text-3xl font-bold text-gray-900">
                    R$ {{ number_format($stats['monthly_consumption'], 2, ',', '.') }}
                </p>
                
                <div class="flex items-center mt-2">
                    <x-icon name="arrow-{{ $stats['consumption_trend'] === 'up' ? 'up' : 'down' }}" 
                            class="h-4 w-4 {{ $stats['consumption_trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}" />
                    <span class="text-sm {{ $stats['consumption_trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $stats['consumption_percentage'] }}% vs mês anterior
                    </span>
                </div>
            </div>
            
            <!-- Faturas Pendentes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Faturas Pendentes</h3>
                    <x-icon name="file-text" class="h-5 w-5 text-warning-500" />
                </div>
                
                <p class="text-3xl font-bold text-gray-900">
                    {{ $stats['pending_invoices_count'] }}
                </p>
                
                @if($stats['overdue_invoices_count'] > 0)
                    <div class="mt-2">
                        <x-badge color="danger">
                            {{ $stats['overdue_invoices_count'] }} vencidas
                        </x-badge>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Tabs -->
        <div x-data="{ tab: 'info' }" class="bg-white rounded-lg shadow-md">
            <!-- Tab Headers -->
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button @click="tab = 'info'"
                            :class="tab === 'info' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm">
                        Informações
                    </button>
                    
                    <button @click="tab = 'invoices'"
                            :class="tab === 'invoices' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm">
                        Faturas
                    </button>
                    
                    <button @click="tab = 'orders'"
                            :class="tab === 'orders' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm">
                        Histórico de Compras
                    </button>
                    
                    <button @click="tab = 'subscription'"
                            :class="tab === 'subscription' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm">
                        Assinatura
                    </button>
                </nav>
            </div>
            
            <!-- Tab Content -->
            <div class="p-6">
                <!-- Informações -->
                <div x-show="tab === 'info'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dados Pessoais</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Nome Completo</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $filho->fullname }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">CPF</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $filho->cpf_formatted }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Data de Nascimento</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $filho->birth_date_formatted }} ({{ $filho->age }} anos)</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Nome da Mãe</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $filho->mother_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Telefone</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $filho->phone_formatted }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $filho->user->email ?? '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Endereço</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Endereço Completo</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $filho->address }}, {{ $filho->address_number }}
                                        @if($filho->address_complement)
                                            <br>{{ $filho->address_complement }}
                                        @endif
                                        <br>{{ $filho->neighborhood }} - {{ $filho->city }}/{{ $filho->state }}
                                        <br>CEP: {{ $filho->zipcode_formatted }}
                                    </dd>
                                </div>
                            </dl>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 mt-6">Configurações</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Limite de Crédito</dt>
                                    <dd class="mt-1 text-sm text-gray-900">R$ {{ number_format($filho->credit_limit, 2, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Dia de Fechamento</dt>
                                    <dd class="mt-1 text-sm text-gray-900">Dia {{ $filho->billing_close_day }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Cadastrado em</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $filho->created_at->format('d/m/Y H:i') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
                
                <!-- Faturas -->
                <div x-show="tab === 'invoices'">
                    <livewire:admin.filhos.filho-invoices :filho="$filho" />
                </div>
                
                <!-- Histórico de Compras -->
                <div x-show="tab === 'orders'">
                    <livewire:admin.filhos.filho-orders :filho="$filho" />
                </div>
                
                <!-- Assinatura -->
                <div x-show="tab === 'subscription'" wire:key="subscription-tab-content">
                    <livewire:admin.filhos.filho-subscription :filho="$filho" />
                </div>
                
            </div>
        </div>
    </div>


<livewire:admin.filhos.adjust-credit-modal :filho="$filho"/>
<livewire:admin.filhos.update-status-filho :filho="$filho"/>

</x-layouts.admin>
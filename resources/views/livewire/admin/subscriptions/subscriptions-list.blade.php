<div>
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center">
            <x-icon name="check-circle" class="w-5 h-5 mr-2" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center">
            <x-icon name="x-circle" class="w-5 h-5 mr-2" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-t-lg border-b">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Busca --}}
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    Buscar
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Nome do filho ou CPF..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                    <x-icon name="magnifying-glass" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                </div>
            </div>

            {{-- Filtro de Status --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                    Status
                </label>
                <select 
                    id="status"
                    wire:model.live="status"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos</option>
                    <option value="active">Ativa</option>
                    <option value="paused">Pausada</option>
                    <option value="cancelled">Cancelada</option>
                    <option value="expired">Expirada</option>
                </select>
            </div>

            {{-- Stats Rápidos --}}
            <div class="flex items-end">
                <div class="text-sm text-gray-600">
                    <div class="flex items-center space-x-4">
                        <div>
                            <span class="font-medium">{{ $stats['active'] }}</span> Ativas
                        </div>
                        <div>
                            <span class="font-medium">{{ $stats['paused'] }}</span> Pausadas
                        </div>
                        <div>
                            <span class="font-medium">{{ $stats['cancelled'] }}</span> Canceladas
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('filho_id')">
                        <div class="flex items-center space-x-1">
                            <span>Filho</span>
                            @if($sortField === 'filho_id')
                                <x-icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-4 h-4" />
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Plano
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('amount')">
                        <div class="flex items-center space-x-1">
                            <span>Valor</span>
                            @if($sortField === 'amount')
                                <x-icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-4 h-4" />
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('next_billing_date')">
                        <div class="flex items-center space-x-1">
                            <span>Próxima Cobrança</span>
                            @if($sortField === 'next_billing_date')
                                <x-icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-4 h-4" />
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('status')">
                        <div class="flex items-center space-x-1">
                            <span>Status</span>
                            @if($sortField === 'status')
                                <x-icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-4 h-4" />
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ações
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-gray-50">
                        {{-- Filho --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold text-sm">
                                        {{ strtoupper(substr($subscription->filho->fullname, 0, 2)) }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $subscription->filho->fullname }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        CPF: {{ $subscription->filho->cpf }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Plano --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $subscription->plan_name }}</div>
                            <div class="text-sm text-gray-500">{{ ucfirst($subscription->billing_cycle) }}</div>
                        </td>

                        {{-- Valor --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                R$ {{ number_format($subscription->amount, 2, ',', '.') }}
                            </div>
                        </td>

                        {{-- Próxima Cobrança --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($subscription->next_billing_date)
                                <div class="text-sm text-gray-900">
                                    {{ $subscription->next_billing_date->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    Dia {{ $subscription->billing_day }}
                                </div>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClasses = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'paused' => 'bg-yellow-100 text-yellow-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    'expired' => 'bg-gray-100 text-gray-800',
                                ];
                                
                                $statusLabels = [
                                    'active' => 'Ativa',
                                    'paused' => 'Pausada',
                                    'cancelled' => 'Cancelada',
                                    'expired' => 'Expirada',
                                ];
                            @endphp
                            
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClasses[$subscription->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$subscription->status] ?? ucfirst($subscription->status) }}
                            </span>
                        </td>

                        {{-- Ações --}}
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                
                               
                                <a 
                                    href="{{ route('admin.subscriptions.show', $subscription) }}"
                                    class="text-blue-600 hover:text-blue-900"
                                    title="Ver detalhes do filho"
                                >
                                    <x-icon name="eye" class="w-8 h-8" />
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <x-icon name="inbox" class="w-12 h-12 text-gray-400 mb-4" />
                                <p class="text-gray-500 text-sm">Nenhuma assinatura encontrada</p>
                                @if($search || $status)
                                    <button 
                                        wire:click="$set('search', ''); $set('status', '')"
                                        class="mt-2 text-blue-600 hover:text-blue-800 text-sm"
                                    >
                                        Limpar filtros
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    @if($subscriptions->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $subscriptions->links() }}
        </div>
    @endif

    {{-- Loading Indicator --}}
    <div wire:loading class="fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg">
        <div class="flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Carregando...</span>
        </div>
    </div>
</div>
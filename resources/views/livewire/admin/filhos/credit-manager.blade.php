{{-- resources/views/livewire/admin/filhos/credit-manager.blade.php --}}
<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- Resumo de Crédito --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            {{-- Limite --}}
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500">Limite de Crédito</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">R$ {{ number_format($filho->credit_limit, 2, ',', '.') }}</p>
                <button wire:click="openLimitModal" class="mt-2 text-xs text-primary-600 hover:text-primary-700">
                    Alterar limite
                </button>
            </div>
            
            {{-- Utilizado --}}
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500">Crédito Utilizado</p>
                <p class="mt-1 text-2xl font-bold text-red-600">R$ {{ number_format($filho->credit_used, 2, ',', '.') }}</p>
            </div>
            
            {{-- Disponível --}}
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500">Crédito Disponível</p>
                <p class="mt-1 text-2xl font-bold text-green-600">R$ {{ number_format($filho->credit_available, 2, ',', '.') }}</p>
            </div>
            
            {{-- Status --}}
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500">Status</p>
                @if($filho->is_blocked)
                    <p class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <x-icon name="lock" class="w-4 h-4 mr-1" />
                        Bloqueado
                    </p>
                @else
                    <p class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <x-icon name="check" class="w-4 h-4 mr-1" />
                        Liberado
                    </p>
                @endif
                <button wire:click="toggleBlock" class="mt-2 text-xs text-gray-600 hover:text-gray-700 block mx-auto">
                    {{ $filho->is_blocked ? 'Desbloquear' : 'Bloquear' }}
                </button>
            </div>
        </div>
        
        {{-- Barra de progresso --}}
        <div class="mt-6">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-500">Utilização</span>
                <span class="font-medium">{{ $filho->credit_limit > 0 ? round(($filho->credit_used / $filho->credit_limit) * 100, 1) : 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                @php $percentage = $filho->credit_limit > 0 ? min(($filho->credit_used / $filho->credit_limit) * 100, 100) : 0; @endphp
                <div class="h-2.5 rounded-full {{ $percentage > 80 ? 'bg-red-500' : ($percentage > 50 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $percentage }}%"></div>
            </div>
        </div>
    </div>

    {{-- Ações --}}
    <div class="flex space-x-3 mb-6">
        <button wire:click="openAdjustModal" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
            <x-icon name="plus-minus" class="w-4 h-4 mr-2" />
            Ajuste Manual
        </button>
    </div>

    {{-- Histórico de Transações --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Histórico de Transações</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo Após</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $transaction->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($transaction->type === 'credit')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Crédito
                                    </span>
                                @elseif($transaction->type === 'debit')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        Débito
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $transaction->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->type === 'credit' ? '+' : '-' }} R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                R$ {{ number_format($transaction->balance_after, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                Nenhuma transação encontrada
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    </div>

    {{-- Modal de Ajuste --}}
    @if($showAdjustModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="closeAdjustModal"></div>
                
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajuste Manual de Crédito</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Ajuste</label>
                            <select wire:model="adjustmentType" class="w-full border-gray-300 rounded-lg">
                                <option value="credit">Adicionar Crédito</option>
                                <option value="debit">Debitar Crédito</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$)</label>
                            <input type="number" wire:model="amount" step="0.01" min="0.01" class="w-full border-gray-300 rounded-lg">
                            @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
                            <textarea wire:model="reason" rows="2" class="w-full border-gray-300 rounded-lg" placeholder="Descreva o motivo do ajuste..."></textarea>
                            @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button wire:click="closeAdjustModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button wire:click="submitAdjustment" class="px-4 py-2 text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de Limite --}}
    @if($showLimitModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="closeLimitModal"></div>
                
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Alterar Limite de Crédito</h3>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">Limite atual: <strong>R$ {{ number_format($filho->credit_limit, 2, ',', '.') }}</strong></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Novo Limite (R$)</label>
                        <input type="number" wire:model="newCreditLimit" step="0.01" min="0" class="w-full border-gray-300 rounded-lg">
                        @error('newCreditLimit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button wire:click="closeLimitModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button wire:click="updateCreditLimit" class="px-4 py-2 text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
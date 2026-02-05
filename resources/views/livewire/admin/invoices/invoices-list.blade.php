{{-- resources/views/livewire/admin/invoices/invoices-list.blade.php --}}
<div>
    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Pendentes</p>
            <p class="text-2xl font-bold text-yellow-600">R$ {{ number_format($stats['total_pending'], 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400">{{ $stats['pending_count'] }} faturas</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Vencidas</p>
            <p class="text-2xl font-bold text-red-600">R$ {{ number_format($stats['total_overdue'], 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400">{{ $stats['overdue_count'] }} faturas</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Recebido (Mês)</p>
            <p class="text-2xl font-bold text-green-600">R$ {{ number_format($stats['paid_this_month'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center justify-center">
            <a href="#" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                Gerar Faturas do Mês →
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Buscar filho ou nº fatura..."
                    class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                >
            </div>
            
            <div>
                <select wire:model.live="statusFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todos os status</option>
                    <option value="pending">Pendente</option>
                    <option value="overdue">Vencida</option>
                    <option value="paid">Paga</option>
                    <option value="cancelled">Cancelada</option>
                </select>
            </div>
            
            <div>
                <select wire:model.live="typeFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todos os tipos</option>
                    <option value="consumption">Consumo</option>
                    <option value="subscription">Mensalidade</option>
                </select>
            </div>
            
            <div>
                <select wire:model.live="periodFilter" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Todos os períodos</option>
                    <option value="this_month">Este mês</option>
                    <option value="last_month">Mês passado</option>
                    <option value="overdue">Apenas vencidas</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fatura</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Filho</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referência</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($invoice->filho->photo_url)
                                        <img class="h-8 w-8 rounded-full object-cover" src="{{ Storage::url($invoice->filho->photo_url) }}" alt="">
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-xs font-medium text-primary-600">{{ substr($invoice->filho->fullname, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <span class="ml-3 text-sm text-gray-900">{{ Str::limit($invoice->filho->fullname, 20) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $invoice->type === 'consumption' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}
                                ">
                                    {{ $invoice->type === 'consumption' ? 'Consumo' : 'Mensalidade' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $invoice->period_start ? \Carbon\Carbon::parse($invoice->period_start)->format('M/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'yellow',
                                        'overdue' => 'red',
                                        'paid' => 'green',
                                        'cancelled' => 'gray',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pendente',
                                        'overdue' => 'Vencida',
                                        'paid' => 'Paga',
                                        'cancelled' => 'Cancelada',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColors[$invoice->status] ?? 'gray' }}-100 text-{{ $statusColors[$invoice->status] ?? 'gray' }}-800">
                                    {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $invoice->status === 'overdue' ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                {{ $invoice->due_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-primary-600 hover:text-primary-900 text-sm  font-medium">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <x-icon name="file-text" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                                <p>Nenhuma fatura encontrada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
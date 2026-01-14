<div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <!-- Header -->
        <div class="px-6 py-5 bg-gradient-to-r from-purple-600 to-purple-700">
            <div class="flex items-center justify-between">
                <div class="text-white">
                    <h3 class="text-2xl font-bold">Fatura #{{ $invoice->invoice_number }}</h3>
                    <p class="text-purple-100">{{ $invoice->period_start?->format('m Y') }}</p>
                </div>
                <div class="flex space-x-2">
                    <button wire:click="downloadPdf" class="px-4 py-2 bg-white text-purple-700 rounded-md hover:bg-purple-50">
                        <svg class="h-4 w-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download PDF
                    </button>
                    @if($invoice->status !== 'paid')
                        <button wire:click="registerPayment" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                            Registrar Pagamento
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status e Informações -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6 bg-gray-50">
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-sm text-gray-500">Status</p>
                <span class="mt-1 inline-flex px-3 py-1 rounded-full text-sm font-medium
                    @if($invoice->status === 'paid') bg-green-100 text-green-800
                    @elseif($invoice->status === 'pending') bg-yellow-100 text-yellow-800
                    @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                    @endif">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-sm text-gray-500">Vencimento</p>
                <p class="mt-1 text-lg font-bold">{{ $invoice->due_date?->format('d/m/Y') }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-sm text-gray-500">Valor Total</p>
                <p class="mt-1 text-2xl font-bold text-purple-600">R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</p>
            </div>
        </div>

        <!-- Detalhes do Filho -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-900 mb-2">Dados do Filho</h4>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Nome</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $invoice->filho->fullname }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">CPF</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $invoice->filho->cpfformatted  }}</dd>
                </div>
            </dl>
        </div>

        <!-- Itens da Fatura -->
        <div class="px-6 py-4">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Itens</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qtd</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Unit.</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Subtotal</td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        @if($invoice->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-green-600">Desconto</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-green-600">- R$ {{ number_format($invoice->discount_amount, 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-lg font-bold text-gray-900">Total</td>
                            <td class="px-6 py-4 text-right text-xl font-bold text-purple-600">R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Histórico de Pagamentos -->
        @if($invoice->payments->count() > 0)
            <div class="px-6 py-4 border-t border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Histórico de Pagamentos</h4>
                <div class="space-y-2">
                    @foreach($invoice->payments as $payment)
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $payment->payment_method }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->paid_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <p class="text-lg font-bold text-green-600">R$ {{ number_format($payment->amount, 2, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
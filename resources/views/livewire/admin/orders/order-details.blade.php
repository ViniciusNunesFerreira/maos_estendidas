
<div>
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-6">
        {{-- Cabeçalho do Pedido --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full bg-{{ $statusLabels[$order->status]['color'] }}-100 flex items-center justify-center">
                        <x-icon name="shopping-bag" class="w-6 h-6 text-{{ $statusLabels[$order->status]['color'] }}-600" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Pedido #{{ $order->order_number }}</h2>
                        <p class="text-sm text-gray-500">
                            {{ $order->created_at->format('d/m/Y \à\s H:i') }}
                            <span class="mx-1">•</span>
                            <span class="capitalize">{{ $order->origin }}</span>
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 md:mt-0">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-{{ $statusLabels[$order->status]['color'] }}-100 text-{{ $statusLabels[$order->status]['color'] }}-800">
                        {{ $statusLabels[$order->status]['label'] }}
                    </span>
                </div>
            </div>
            
            {{-- Ações de Status --}}
            @if(!empty($nextActions))
                <div class="mt-6 pt-6 border-t border-gray-200 flex flex-wrap gap-3">
                    @foreach($nextActions as $status => $label)
                        @if($status === 'cancelled')
                            <button 
                                wire:click="openCancelModal"
                                class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 rounded-lg text-sm font-medium hover:bg-red-50"
                            >
                                <x-icon name="x" class="w-4 h-4 mr-2" />
                                {{ $label }}
                            </button>
                        @else
                            <button 
                                wire:click="updateStatus('{{ $status }}')"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700"
                            >
                                <x-icon name="check" class="w-4 h-4 mr-2" />
                                {{ $label }}
                            </button>
                        @endif
                    @endforeach
                    
                    <button 
                        wire:click="printOrder"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50"
                    >
                        <x-icon name="printer" class="w-4 h-4 mr-2" />
                        Imprimir
                    </button>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Itens do Pedido --}}
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Itens do Pedido</h3>
                </div>
                
                <div class="divide-y divide-gray-200">
                    @foreach($order->items as $item)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                @if($item->product->image_url)
                                    <img src="{{ $item->product->image_url }}" class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                        <x-icon name="package" class="w-6 h-6 text-gray-400" />
                                    </div>
                                @endif
                                
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $item->product->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $item->quantity }}x R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                            
                            <p class="text-sm font-semibold text-gray-900">
                                R$ {{ number_format($item->subtotal, 2, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total</span>
                        <span class="text-2xl font-bold text-gray-900">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Informações Laterais --}}
            <div class="space-y-6">
                {{-- Cliente --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Cliente</h3>
                    
                    @if($order->filho)
                        <div class="flex items-center space-x-4">
                            @if($order->filho->photo_url)
                                <img src="{{ $order->filho->photo_url }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-lg font-semibold text-primary-600">{{ substr($order->filho->full_name, 0, 1) }}</span>
                                </div>
                            @endif
                            
                            <div>
                                <p class="font-medium text-gray-900">{{ $order->filho->full_name }}</p>
                                <a href="{{ route('admin.filhos.show', $order->filho) }}" class="text-sm text-primary-600 hover:text-primary-700">
                                    Ver perfil →
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                <x-icon name="user" class="w-6 h-6 text-gray-400" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $order->guest_name ?? 'Visitante' }}</p>
                                @if($order->guest_document)
                                    <p class="text-sm text-gray-500">{{ $order->guest_document }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Pagamento --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pagamento</h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Método:</span>
                            <span class="text-gray-900 font-medium">
                                @php
                                    $methodLabels = [
                                        'pix' => 'PIX',
                                        'credito' => 'Cartão de Crédito',
                                        'debito' => 'Cartão de Débito',
                                        'dinheiro' => 'Dinheiro',
                                        'carteira' => 'Crédito Filho',
                                    ];
                                @endphp
                                {{ $methodLabels[$order->payment_method_chosen] ?? $order->payment_method_chosen ?? 'Crédito Filho' }}
                            </span>
                        </div>
                        @if($order->payment_confirmed_at)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Confirmado em:</span>
                                <span class="text-gray-900">{{ $order->payment_confirmed_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Observações --}}
                @if($order->notes)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Observações</h3>
                        <p class="text-sm text-gray-600">{{ $order->notes }}</p>
                    </div>
                @endif

                {{-- Operador --}}
                @if($order->createdBy)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Operador</h3>
                        <p class="text-sm text-gray-600">{{ $order->createdBy->name }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal de Cancelamento --}}
    @if($showCancelModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="closeCancelModal"></div>
                
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Cancelar Pedido</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Esta ação irá estornar o crédito do cliente e devolver os itens ao estoque.
                    </p>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Motivo do cancelamento *</label>
                        <textarea 
                            wire:model="cancelReason" 
                            rows="3" 
                            class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Informe o motivo..."
                        ></textarea>
                        @error('cancelReason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button wire:click="closeCancelModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Voltar
                        </button>
                        <button wire:click="confirmCancel" class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Confirmar Cancelamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
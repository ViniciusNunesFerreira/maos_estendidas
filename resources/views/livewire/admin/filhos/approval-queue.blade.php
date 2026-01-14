{{-- resources/views/livewire/admin/filhos/approval-queue.blade.php --}}
<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Buscar por nome ou CPF..."
                    class="w-full md:w-80 border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                >
            </div>
            <div class="text-sm text-gray-500">
                {{ $pendingFilhos->total() }} cadastros pendentes
            </div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="space-y-4">
        @forelse($pendingFilhos as $filho)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    {{-- Foto e Info Básica --}}
                    <div class="flex items-start space-x-4">
                        @if($filho->photo_url)
                            <img src="{{ Storage::url($filho->photo_url) }}" alt="{{ $filho->fullname }}" class="w-20 h-20 rounded-xl object-cover">
                        @else
                            <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center">
                                <x-icon name="user" class="w-8 h-8 text-gray-400" />
                            </div>
                        @endif
                        
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $filho->fullname }}</h3>
                            <p class="text-sm text-gray-500">CPF: {{ $filho->cpf_formatted }}</p>
                            @if($filho->phone)
                                <p class="text-sm text-gray-500">Tel: {{ $filho->phone }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">
                                Solicitado em {{ $filho->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    
                    {{-- Dados Adicionais --}}
                    <div class="flex-1 grid grid-cols-1  gap-4 text-sm">
                        @if($filho->birth_date)
                            <div>
                                <span class="text-gray-500">Data de Nascimento:</span>
                                <span class="text-gray-900 ml-1">{{ $filho->birth_date->format('d/m/Y') }}</span>
                            </div>
                        @endif
                        @if($filho->address)
                            <div>
                                <span class="text-gray-500">Endereço:</span>
                                <span class="text-gray-900 ml-1">{{ Str::limit($filho->address, 50) }}</span>
                            </div>
                        @endif
                        @if($filho->user?->email)
                            <div>
                                <span class="text-gray-500">Email:</span>
                                <span class="text-gray-900 ml-1">{{ $filho->user->email }}</span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Ações --}}
                    <div class="flex flex-col space-y-2">
                        <button 
                            wire:click="approve('{{ $filho->id }}')"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50"
                        >
                            <x-icon name="check" class="w-4 h-4 mr-2" />
                            Aprovar
                        </button>
                        
                        <button 
                            x-data=""
                            x-on:click="$dispatch('open-reject-modal', { filhoId: '{{ $filho->id }}', filhoName: '{{ $filho->name }}' })"
                            class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700"
                        >
                            <x-icon name="x" class="w-4 h-4 mr-2" />
                            Rejeitar
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <x-icon name="check-circle" class="w-16 h-16 mx-auto mb-4 text-green-300" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum cadastro pendente</h3>
                <p class="text-gray-500">Todos os cadastros foram processados.</p>
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    <div class="mt-6">
        {{ $pendingFilhos->links() }}
    </div>

    {{-- Modal de Rejeição --}}
    <div 
        x-data="{ 
            open: false, 
            filhoId: null, 
            filhoName: '',
            reason: ''
        }"
        x-on:open-reject-modal.window="open = true; filhoId = $event.detail.filhoId; filhoName = $event.detail.filhoName; reason = ''"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="open" x-transition:enter="ease-out duration-300" class="fixed inset-0 bg-black bg-opacity-50"></div>
            
            <div x-show="open" x-transition:enter="ease-out duration-300" class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Rejeitar Cadastro</h3>
                <p class="text-sm text-gray-600 mb-4">Informe o motivo da rejeição para <strong x-text="filhoName"></strong>:</p>
                
                <textarea 
                    x-model="reason"
                    rows="3"
                    class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 mb-4"
                    placeholder="Motivo da rejeição..."
                ></textarea>
                
                <div class="flex justify-end space-x-3">
                    <button 
                        @click="open = false"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="$wire.reject(filhoId, reason); open = false"
                        class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700"
                    >
                        Confirmar Rejeição
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Métodos de Pagamento Ativos</h3>
            <p class="mt-1 text-sm text-gray-600">Selecione os métodos disponíveis</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach($availableMethods as $key => $method)
                    <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all {{ in_array($key, $selectedMethods) ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' : 'border-gray-300 hover:bg-gray-50' }}">
                        <input 
                            type="checkbox"
                            wire:click="toggleMethod('{{ $key }}')"
                            {{ in_array($key, $selectedMethods) ? 'checked' : '' }}
                            class="sr-only"
                        >
                        <div class="flex flex-1 items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full {{ in_array($key, $selectedMethods) ? 'bg-blue-100' : 'bg-gray-100' }}">
                                <svg class="h-5 w-5 {{ in_array($key, $selectedMethods) ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $method['icon'] }}"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-gray-900">{{ $method['name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $method['description'] }}</div>
                            </div>
                            @if(in_array($key, $selectedMethods))
                                <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end border-t pt-4">
                <button 
                    wire:click="save"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salvar Métodos
                </button>
            </div>
        </div>
    </div>
</div>
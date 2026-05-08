<div>
    <button wire:click="confirmDeletion" class="px-6 py-3 text-lg gap-2.5 inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 bg-red-600 text-white hover:bg-red-700 focus:ring-red-500">
        <svg class="w-5 h-5 mr-4 " fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        Excluir Definitivamente
    </button>


    @if($confirmingFilhoDeletion)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 mx-4">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            
            <h3 class="text-lg font-bold text-center text-gray-900 mb-2">Atenção! Ação Irreversível</h3>
            <p class="text-sm text-gray-500 text-center mb-4">
                Você está prestes a excluir o filho <strong>{{ $filho->full_name }}</strong>. 
                Isso apagará <strong>todos</strong> os pedidos, faturas, pagamentos e transações vinculadas. 
                Esta ação não poderá ser desfeita.
            </p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Para confirmar, digite <b>EXCLUIR</b> no campo abaixo:</label>
                <input type="text" wire:model="confirmationText" class="form-input w-full border-gray-300 rounded-md" placeholder="EXCLUIR">
                @error('confirmationText') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('confirmingFilhoDeletion', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="deleteFilho" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Sim, Apagar Tudo
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
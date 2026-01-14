<!-- resources/views/livewire/payment-register.blade.php -->
<div>
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Registrar Pagamento</h3>

        <form wire:submit="registerPayment">
            <!-- Valor -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Valor</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">R$</span>
                    </div>
                    <input type="text" wire:model="amount" class="pl-12 block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="0,00">
                </div>
                @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Método de Pagamento -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pagamento</label>
                <select wire:model="paymentMethod" class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Selecione...</option>
                    <option value="cash">Dinheiro</option>
                    <option value="credit_card">Cartão de Crédito</option>
                    <option value="debit_card">Cartão de Débito</option>
                    <option value="pix">PIX</option>
                    <option value="bank_transfer">Transferência Bancária</option>
                </select>
                @error('paymentMethod') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Data do Pagamento -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Data do Pagamento</label>
                <input type="datetime-local" wire:model="paidAt" class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                @error('paidAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Observações -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                <textarea wire:model="notes" rows="3" class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
            </div>

            <!-- Botões -->
            <div class="flex justify-end space-x-3">
                <button type="button" wire:click="$dispatch('closeModal')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700">
                    Registrar Pagamento
                </button>
            </div>
        </form>
    </div>
</div>
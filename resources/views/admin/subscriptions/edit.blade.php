<x-layouts.admin>

    <x-slot name="title">Nova Assinatura</x-slot>

    <div class="space-y-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Editar Assinatura</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $subscription->filho->user->name ?? $subscription->filho->name ?? 'N/A' }}</p>
        </div>

        <form action="{{ route('admin.subscriptions.update', $subscription->id) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- Nome do Plano -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Plano <span class="text-red-500">*</span></label>
                    <input type="text" name="plan_name" value="{{ old('plan_name', $subscription->plan_name) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('plan_name') border-red-500 @enderror">
                    @error('plan_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Valor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valor (R$) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount', $subscription->amount) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('amount') border-red-500 @enderror">
                        @error('amount')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Dia de Cobrança -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dia de Cobrança <span class="text-red-500">*</span></label>
                        <input type="number" name="billing_day" min="1" max="28" value="{{ old('billing_day', $subscription->billing_day) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('billing_day') border-red-500 @enderror">
                        @error('billing_day')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-2">Periodo de Recorrencia <span class="text-red-500">*</span></label>
                        <select name="billing_cycle" id="billing_cycle" class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('billing_cycle') border-red-500 @enderror">
                            @php 
                                $billing_cycles_values = array( [  'key' => 'monthly' , 'name' => 'Mensal'], 
                                                                [  'key' => 'quarterly', 'name' => 'Trimestral'],
                                                                [  'key' => 'yearly', 'name' => 'Anual'] );
                            @endphp
                                <option> Selecione um Periodo </option>
                            @foreach($billing_cycles_values as $blc )
                                <option value="{{$blc['key'] }}" @selected( old('billing_cycle', $subscription->billing_cycle) == $blc['key'] ) > {{ $blc['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Descrição -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição (opcional)</label>
                    <textarea name="plan_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('plan_description', $subscription->plan_description) }}</textarea>
                </div>
            </div>

            <div class="mt-8 flex gap-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Salvar Alterações
                </button>
                <a href="{{ route('admin.subscriptions.show', $subscription->id) }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

</x-layouts.admin>
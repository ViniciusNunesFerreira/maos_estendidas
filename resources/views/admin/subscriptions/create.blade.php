<x-layouts.admin>
<x-slot name="title">Nova Assinatura</x-slot>

    <div class="space-y-6 ">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Nova Assinatura</h1>
            <p class="mt-2 text-sm text-gray-600">Criar assinatura mensal para um filho</p>
        </div>

        <form action="{{ route('admin.subscriptions.store') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            @csrf
            
            <div class="space-y-6">
                <!-- Select Filho -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filho <span class="text-red-500">*</span></label>
                    <select name="filho_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('filho_id') border-red-500 @enderror">
                        <option value="">Selecione um filho</option>
                        @foreach($filhos as $filho)
                            <option value="{{ $filho->id }}" {{ old('filho_id') == $filho->id || ($selectedFilho && $selectedFilho->id == $filho->id) ? 'selected' : '' }}>
                                {{ $filho->user->name ?? $filho->name ?? 'N/A' }} - CPF: {{ substr($filho->cpf, 0, 3) }}.***.***-{{ substr($filho->cpf, -2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('filho_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Nome do Plano -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Plano <span class="text-red-500">*</span></label>
                    <input type="text" name="plan_name" value="{{ old('plan_name', 'Mensalidade Mãos Estendidas') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('plan_name') border-red-500 @enderror">
                    @error('plan_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Valor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valor (R$) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount', '120.00') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('amount') border-red-500 @enderror">
                        @error('amount')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Ciclo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ciclo <span class="text-red-500">*</span></label>
                        <select name="billing_cycle" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('billing_cycle') border-red-500 @enderror">
                            <option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>Mensal</option>
                            <option value="quarterly" {{ old('billing_cycle') == 'quarterly' ? 'selected' : '' }}>Trimestral</option>
                            <option value="yearly" {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Anual</option>
                        </select>
                        @error('billing_cycle')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Dia de Cobrança -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dia de Cobrança <span class="text-red-500">*</span></label>
                        <input type="number" name="billing_day" min="1" max="28" value="{{ old('billing_day', 10) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('billing_day') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Escolha entre 1 e 28</p>
                        @error('billing_day')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Data de Início -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Início</label>
                        <input type="date" name="started_at" value="{{ old('started_at', now()->format('Y-m-d')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('started_at') border-red-500 @enderror">
                        @error('started_at')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Descrição -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição (opcional)</label>
                    <textarea name="plan_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('plan_description') border-red-500 @enderror">{{ old('plan_description') }}</textarea>
                    @error('plan_description')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-8 flex gap-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Criar Assinatura
                </button>
                <a href="{{ route('admin.subscriptions.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

</x-layouts.admin>
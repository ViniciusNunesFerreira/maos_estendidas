<div class="space-y-6">
    
    {{-- Card de Credenciais --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Credenciais do Mercado Pago</h3>
            <p class="mt-1 text-sm text-gray-600">Configure as chaves de acesso à API</p>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            
            {{-- Ambiente --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ambiente</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all {{ $environment === 'sandbox' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' : 'border-gray-300 bg-white hover:bg-gray-50' }}">
                        <input 
                            type="radio" 
                            wire:model.live="environment" 
                            value="sandbox" 
                            class="sr-only"
                        >
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $environment === 'sandbox' ? 'bg-yellow-100' : 'bg-gray-100' }}">
                                <svg class="h-5 w-5 {{ $environment === 'sandbox' ? 'text-yellow-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold {{ $environment === 'sandbox' ? 'text-blue-900' : 'text-gray-900' }}">Sandbox (Teste)</div>
                                <div class="text-xs text-gray-500">Para desenvolvimento</div>
                            </div>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all {{ $environment === 'production' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' : 'border-gray-300 bg-white hover:bg-gray-50' }}">
                        <input 
                            type="radio" 
                            wire:model.live="environment" 
                            value="production" 
                            class="sr-only"
                        >
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $environment === 'production' ? 'bg-blue-100' : 'bg-gray-100' }}">
                                <svg class="h-5 w-5 {{ $environment === 'production' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold {{ $environment === 'production' ? 'text-blue-900' : 'text-gray-900' }}">Produção</div>
                                <div class="text-xs text-gray-500">Para pagamentos reais</div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Access Token --}}
            <div>
                <label for="access_token" class="block text-sm font-medium text-gray-700 mb-1">
                    Access Token (Privado)
                </label>
                <div class="relative">
                    <input 
                        type="{{ $showTokens ? 'text' : 'password' }}"
                        wire:model="access_token"
                        id="access_token"
                        placeholder="{{ $environment === 'sandbox' ? 'TEST-xxxxx' : 'APP_USR-xxxxx' }}"
                        class="block w-full rounded-lg border-gray-300 pr-24 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    <button 
                        type="button"
                        wire:click="$toggle('showTokens')"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                    >
                        @if($showTokens)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        @endif
                    </button>
                </div>
                @error('access_token')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Public Key --}}
            <div>
                <label for="public_key" class="block text-sm font-medium text-gray-700 mb-1">
                    Public Key
                </label>
                <input 
                    type="{{ $showTokens ? 'text' : 'password' }}"
                    wire:model="public_key"
                    id="public_key"
                    placeholder="{{ $environment === 'sandbox' ? 'TEST-xxxxx' : 'APP_USR-xxxxx' }}"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                @error('public_key')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>


            {{-- Webhook Secret --}}
            <div>
                <label for="webhook_secret" class="block text-sm font-medium text-gray-700 mb-1">
                    Webhook Secret (Privado)
                </label>
                <div class="relative">
                    <input 
                        type="{{ $showSecret ? 'text' : 'password' }}"
                        wire:model="webhook_secret"
                        id="webhook_secret"
                        class="block w-full rounded-lg border-gray-300 pr-24 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    <button 
                        type="button"
                        wire:click="$toggle('showSecret')"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                    >
                        @if($showSecret)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        @endif
                    </button>
                </div>
                @error('webhook_secret')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botões --}}
            <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-200">
                <button 
                    type="button"
                    wire:click="testConnection"
                    wire:loading.attr="disabled"
                    wire:target="testConnection"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg wire:loading.remove wire:target="testConnection" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg wire:loading wire:target="testConnection" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="testConnection">Testar Conexão</span>
                    <span wire:loading wire:target="testConnection">Testando...</span>
                </button>

                <div class="flex items-center gap-3">
                    <button 
                        type="button"
                        wire:click="toggleActive"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium {{ $config->is_active ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-green-600 text-white hover:bg-green-700' }}"
                    >
                        @if($config->is_active)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            Desativar
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Ativar
                        @endif
                    </button>

                    <button 
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg wire:loading.remove class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Salvar Credenciais
                    </button>
                </div>
            </div>

        </form>
    </div>

    {{-- Resultado do teste --}}
    @if($testResult)
        <div class="rounded-lg border p-4 {{ $testResult['success'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    @if($testResult['success'])
                        <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @else
                        <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @endif
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold {{ $testResult['success'] ? 'text-green-800' : 'text-red-800' }}">
                        {{ $testResult['success'] ? 'Conexão bem-sucedida!' : 'Erro na conexão' }}
                    </h4>
                    <p class="mt-1 text-sm {{ $testResult['success'] ? 'text-green-700' : 'text-red-700' }}">
                        {{ $testResult['message'] }}
                    </p>
                    @if($testResult['success'] && isset($testResult['environment']))
                        <div class="mt-2 flex items-center gap-4 text-xs {{ $testResult['success'] ? 'text-green-600' : 'text-red-600' }}">
                            <span>Ambiente: <strong>{{ $testResult['environment'] }}</strong></span>
                            @if(isset($testResult['payment_methods_count']))
                                <span>Métodos disponíveis: <strong>{{ $testResult['payment_methods_count'] }}</strong></span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Último teste --}}
    @if($config->tested_at)
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <p class="text-sm text-gray-600">
                Último teste: <strong>{{ $config->tested_at->format('d/m/Y H:i') }}</strong>
            </p>
        </div>
    @endif

</div>
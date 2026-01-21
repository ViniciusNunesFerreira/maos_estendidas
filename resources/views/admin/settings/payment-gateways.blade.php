<x-layouts.admin  :title="$title" >
    
    {{-- Header com ações --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configurações de Pagamento</h1>
            <p class="mt-1 text-sm text-gray-600">Gerencie a integração com Mercado Pago</p>
        </div>
        
        <div class="flex items-center gap-3">
            {{-- Status da integração --}}
            @if($config->is_active)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Ativo
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    Inativo
                </span>
            @endif
            
            {{-- Ambiente --}}
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $config->environment === 'production' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ $config->environment === 'production' ? 'Produção' : 'Sandbox' }}
            </span>
        </div>
    </div>

    <div x-data="{ activeTab: 'credentials' }">
        
        {{-- Tabs Navigation --}}
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button 
                    @click="activeTab = 'credentials'"
                    :class="activeTab === 'credentials' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Credenciais
                    </div>
                </button>

                <button 
                    @click="activeTab = 'point'"
                    :class="activeTab === 'point' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Point / TEF
                    </div>
                </button>

                <button 
                    @click="activeTab = 'methods'"
                    :class="activeTab === 'methods' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Métodos de Pagamento
                    </div>
                </button>

                <button 
                    @click="activeTab = 'devices'"
                    :class="activeTab === 'devices' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        Maquininhas
                    </div>
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div>
            {{-- TAB: Credenciais --}}
            <div x-show="activeTab === 'credentials'" x-cloak>
                <livewire:admin.settings.mercado-pago-credentials :config="$config" />
            </div>

            {{-- TAB: Point/TEF --}}
            <div x-show="activeTab === 'point'" x-cloak>
                <livewire:admin.settings.mercado-pago-point :config="$config" />
            </div>

            {{-- TAB: Métodos --}}
            <div x-show="activeTab === 'methods'" x-cloak>
                <livewire:admin.settings.mercado-pago-methods :config="$config" />
            </div>

            {{-- TAB: Devices --}}
            <div x-show="activeTab === 'devices'" x-cloak>
               <livewire:admin.settings.point-devices :devices="$devices" />
            </div>
        </div>
        
    </div>



    {{-- Card de ajuda --}}
    <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-blue-800">Precisa de ajuda?</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Obtenha suas credenciais em <a href="https://www.mercadopago.com.br/developers" target="_blank" class="font-medium underline">Mercado Pago Developers</a></li>
                        <li>Use credenciais de <strong>TEST</strong> no ambiente Sandbox</li>
                        <li>Certifique-se de configurar o webhook em: <code class="rounded bg-blue-100 px-1">{{ route('api.webhooks.mercadopago') }}</code></li>
                        <li>Para Point/TEF, obtenha o Device ID da maquininha na API do Mercado Pago</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</x-layouts.admin>
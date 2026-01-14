{{-- resources/views/admin/settings/index.blade.php --}}
<x-layouts.admin title="Configurações">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configurações</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Gerencie as configurações do sistema
                </p>
            </div>
        </div>

        {{-- Tabs de Configuração --}}
        <div x-data="{ tab: 'general' }" class="bg-white rounded-lg shadow-md">
            {{-- Tab Headers --}}
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button 
                        @click="tab = 'general'"
                        :class="tab === 'general' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 text-sm font-medium border-b-2 focus:outline-none"
                    >
                        <x-icon name="settings" class="w-4 h-4 inline-block mr-2" />
                        Geral
                    </button>
                    
                    <button 
                        @click="tab = 'subscription'"
                        :class="tab === 'subscription' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 text-sm font-medium border-b-2 focus:outline-none"
                    >
                        <x-icon name="credit-card" class="w-4 h-4 inline-block mr-2" />
                        Assinaturas
                    </button>
                    
                    <button 
                        @click="tab = 'fiscal'"
                        :class="tab === 'fiscal' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 text-sm font-medium border-b-2 focus:outline-none"
                    >
                        <x-icon name="file-text" class="w-4 h-4 inline-block mr-2" />
                        Fiscal / SAT
                    </button>
                </nav>
            </div>

            {{-- Tab Content --}}
            <div class="p-6">
                <div x-show="tab === 'general'">
                    <livewire:admin.settings.general-settings />
                </div>
                
                <div x-show="tab === 'subscription'" x-cloak>
                    <livewire:admin.settings.subscription-settings />
                </div>
                
                <div x-show="tab === 'fiscal'" x-cloak>
                    <livewire:admin.settings.fiscal-settings />
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
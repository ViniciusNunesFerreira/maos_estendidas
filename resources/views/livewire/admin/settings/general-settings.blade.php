{{-- resources/views/livewire/admin/settings/general-settings.blade.php --}}
<div>
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Informações da Instituição --}}
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Informações da Instituição</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Instituição *</label>
                    <input type="text" wire:model="institution_name" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    @error('institution_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                    <input type="text" wire:model="institution_cnpj" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="00.000.000/0000-00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" wire:model="institution_phone" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="(13) 3333-3333">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" wire:model="institution_email" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                    <input type="text" wire:model="institution_address" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
        </div>

        {{-- Logo --}}
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Logo</h4>
            
            <div class="flex items-start space-x-6">
                <div class="flex-shrink-0">
                    @if($logo)
                        <img src="{{ $logo->temporaryUrl() }}" class="w-32 h-32 object-contain rounded-lg border">
                    @elseif($currentLogo)
                        <img src="{{ Storage::url($currentLogo) }}" class="w-32 h-32 object-contain rounded-lg border">
                    @else
                        <div class="w-32 h-32 bg-gray-100 rounded-lg flex items-center justify-center border">
                            <x-icon name="image" class="w-12 h-12 text-gray-300" />
                        </div>
                    @endif
                </div>
                
                <div class="flex-1">
                    <input type="file" wire:model="logo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <p class="text-xs text-gray-500 mt-2">PNG, JPG ou SVG. Recomendado: 200x200px</p>
                    
                    @if($currentLogo)
                        <button type="button" wire:click="removeLogo" class="mt-2 text-sm text-red-600 hover:text-red-700">
                            Remover logo
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Horário de Funcionamento --}}
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Horário de Funcionamento</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abertura</label>
                    <input type="time" wire:model="opening_time" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fechamento</label>
                    <input type="time" wire:model="closing_time" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="open_weekends" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Aberto nos finais de semana</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Configurações Regionais --}}
        <div class="pb-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Configurações Regionais</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuso Horário</label>
                    <select wire:model="timezone" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="America/Sao_Paulo">São Paulo (BRT)</option>
                        <option value="America/Fortaleza">Fortaleza (BRT)</option>
                        <option value="America/Manaus">Manaus (AMT)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Moeda</label>
                    <select wire:model="currency" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="BRL">Real (R$)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Formato de Data</label>
                    <select wire:model="date_format" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="d/m/Y">DD/MM/AAAA</option>
                        <option value="Y-m-d">AAAA-MM-DD</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Ações --}}
        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center px-6 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50">
                <span wire:loading.remove>Salvar Configurações</span>
                <span wire:loading>Salvando...</span>
            </button>
        </div>
    </form>
</div>
<form wire:submit.prevent="save">
    <div class="space-y-6">
        <!-- Dados Pessoais -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dados Pessoais</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <x-forms.input 
                        wire:model="name" 
                        name="name"
                        id="name"
                        label="Nome Completo *" 
                        error="{{ $errors->first('name') }}" 
                    />
                </div>
                
                <x-forms.input 
                    wire:model.blur="cpf"
                    name="cpf"
                    id="cpf" 
                    label="CPF *" 
                    mask="cpf"
                    error="{{ $errors->first('cpf') }}" 
                />
                
                <x-forms.input 
                    wire:model="birth_date"
                    name="birth_date"
                    id="birth_date" 
                    type="date"
                    label="Data de Nascimento *" 
                    error="{{ $errors->first('birth_date') }}" 
                />
                
                <x-forms.input 
                    wire:model="mother_name"
                    name="mother_name"
                    id="mother_name" 
                    label="Nome da Mãe *" 
                    error="{{ $errors->first('mother_name') }}" 
                />
                
                <x-forms.input 
                    wire:model.blur="phone"
                    name="phone"
                    id="phone" 
                    label="Telefone *" 
                    mask="phone"
                    error="{{ $errors->first('phone') }}" 
                />
                
                <x-forms.input 
                    wire:model="email"
                    name="email"
                    id="email" 
                    type="email"
                    label="Email" 
                    error="{{ $errors->first('email') }}" 
                />
                
                @unless($isEditing)
                    <x-forms.input 
                        wire:model="password"
                        name="password"
                        id="password" 
                        type="password"
                        label="Senha *" 
                        error="{{ $errors->first('password') }}" 
                    />
                    
                    <x-forms.input 
                        wire:model="password_confirmation" 
                        type="password"
                        label="Confirmar Senha *" 
                    />
                @endunless
            </div>
        </div>
        
        <!-- Endereço -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Endereço</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-forms.input 
                    wire:model.live.debounce.500ms="zipcode"
                    name="zipcode"
                    id="zipcode" 
                    label="CEP *" 
                    mask="cep"
                    error="{{ $errors->first('zipcode') }}" 
                />
                
                <div class="md:col-span-2">
                    <x-forms.input 
                        wire:model="address"
                        wire:key="input-address-{{ $zipcode }}"
                        name="address"
                        id="address" 
                        label="Rua *" 
                        error="{{ $errors->first('address') }}" 
                    />

                    <div wire:loading wire:target="searchAddress" class="absolute right-2 top-10">
                        <span class="text-xs text-gray-400">Buscando...</span>
                    </div>
                </div>
                
                <x-forms.input 
                    wire:model="number"
                    name="number"
                    id="number" 
                    label="Número *" 
                    error="{{ $errors->first('number') }}" 
                />
                
                <x-forms.input 
                    wire:model="complement"
                    name="complement"
                    id="complement" 
                    label="Complemento" 
                    error="{{ $errors->first('complement') }}" 
                />
                
                <x-forms.input 
                    wire:model="neighborhood" 
                    wire:key="input-neighborhood-{{ $zipcode }}"
                    name="neighborhood"
                    id="neighborhood"
                    label="Bairro *" 
                    error="{{ $errors->first('neighborhood') }}" 
                />
                
                <x-forms.input 
                    wire:model="city"
                    wire:key="input-city-{{ $zipcode }}"
                    name="city"
                    id="city" 
                    label="Cidade *" 
                    error="{{ $errors->first('city') }}" 
                />
                
                <x-forms.select 
                    wire:model="state"
                    wire:key="input-state-{{ $zipcode }}"
                    name="state"
                    id="state" 
                    label="Estado *" 
                    error="{{ $errors->first('state') }}">
                    <option value="">Selecione...</option>
                    <option value="AC">Acre</option>
                    <option value="AL">Alagoas</option>
                    <option value="CE">Ceará</option>
                    <option value="SP">São Paulo</option>
                </x-forms.select>
            </div>
        </div>
        
        <!-- Configurações -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configurações</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-forms.input 
                    wire:model="credit_limit" 
                    name="credit_limit"
                    id="credit_limit"
                    type="number"
                    step="0.01"
                    label="Limite de Crédito (R$)" 
                    error="{{ $errors->first('credit_limit') }}" 
                />
                
                <x-forms.input 
                    wire:model="billing_close_day"
                    name="billing_close_day"
                    id="billing_close_day" 
                    type="number"
                    min="1"
                    max="28"
                    label="Dia de Fechamento *" 
                    error="{{ $errors->first('billing_close_day') }}" 
                />
                
                @if($isEditing)
                    <x-forms.select 
                        wire:model="status" 
                        label="Status *" 
                        error="{{ $errors->first('status') }}">
                        <option value="pending">Pendente</option>
                        <option value="active">Ativo</option>
                        <option value="blocked">Bloqueado</option>
                        <option value="inactive">Inativo</option>
                    </x-forms.select>
                @endif
            </div>
        </div>
        
        <!-- Foto -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Foto</h3>
            
            @if($currentPhoto)
                <div class="mb-4">
                    <img src="{{ $currentPhoto }}" alt="Foto atual" class="h-32 w-32 rounded-full object-cover">
                </div>
            @endif
            
            <x-forms.file-upload 
                wire:model="photo" 
                accept="image/*"
                label="Nova Foto" 
                error="{{ $errors->first('photo') }}" 
                name="photo"
                id="photo"
            />
            
            @if($photo)
                <div class="mt-4">
                    <img src="{{ $photo->temporaryUrl() }}" class="h-32 w-32 rounded-full object-cover">
                </div>
            @endif
        </div>
        
        <!-- Botões -->
        <div class="flex justify-end space-x-3">
            <x-button 
                type="button"
                variant="secondary"
                href="{{ route('admin.filhos.index') }}">
                Cancelar
            </x-button>
            
            <x-button type="submit">
                {{ $isEditing ? 'Atualizar' : 'Cadastrar' }}
            </x-button>
        </div>
    </div>
</form>
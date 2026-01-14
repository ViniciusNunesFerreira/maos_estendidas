<div class="form-group">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div 
        x-data="{ 
            fileName: '{{ $preview ? basename($preview) : '' }}',
            filePreview: '{{ $preview ?? '' }}',
            isImage: {{ $preview && in_array(strtolower(pathinfo($preview, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'true' : 'false' }},
            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.fileName = file.name;
                    
                    // Verificar tamanho do arquivo
                    const maxSize = {{ $maxSize }} * 1024; // Converter KB para bytes
                    if (file.size > maxSize) {
                        alert('Arquivo muito grande. Tamanho máximo: {{ $maxSize }}KB');
                        event.target.value = '';
                        this.fileName = '';
                        this.filePreview = '';
                        return;
                    }
                    
                    // Preview para imagens
                    if (file.type.startsWith('image/')) {
                        this.isImage = true;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.filePreview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        this.isImage = false;
                        this.filePreview = '';
                    }
                }
            },
            removeFile() {
                this.fileName = '';
                this.filePreview = '';
                this.isImage = false;
                $refs.fileInput.value = '';
            }
        }"
        class="space-y-3"
    >
        {{-- Preview de Imagem --}}
        <div x-show="isImage && filePreview" class="mb-3">
            <div class="relative inline-block">
                <img 
                    :src="filePreview" 
                    alt="Preview" 
                    class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300"
                >
                <button 
                    type="button"
                    @click="removeFile"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Input de Arquivo --}}
        <div class="flex items-center justify-center w-full">
            <label 
                for="{{ $name }}" 
                class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors {{ $hasError() ? 'border-red-500' : '' }}"
            >
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-8 h-8 mb-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <p class="mb-1 text-sm text-gray-500">
                        <span class="font-semibold">Clique para enviar</span> ou arraste
                    </p>
                    <p class="text-xs text-gray-500" x-show="!fileName">
                        @if($accept)
                            Arquivos: {{ str_replace(',', ', ', $accept) }}
                        @else
                            Qualquer tipo de arquivo
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">Tamanho máximo: {{ number_format($maxSize / 1024, 0) }}MB</p>
                    <p class="text-sm font-medium text-primary-600 mt-2" x-show="fileName" x-text="fileName"></p>
                </div>
                <input 
                    x-ref="fileInput"
                    id="{{ $name }}" 
                    name="{{ $name }}" 
                    type="file" 
                    class="hidden"
                    @if($accept) accept="{{ $accept }}" @endif
                    @if($required) required @endif
                    @if($multiple) multiple @endif
                    @change="handleFileSelect($event)"
                    {{ $attributes->except(['class', 'wire:model']) }}
                >
            </label>
        </div>

        {{-- Arquivo Selecionado (não-imagem) --}}
        <div x-show="fileName && !isImage" class="flex items-center justify-between p-3 bg-gray-100 rounded-lg">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-sm text-gray-700 font-medium" x-text="fileName"></span>
            </div>
            <button 
                type="button"
                @click="removeFile"
                class="text-red-500 hover:text-red-700 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    </div>

    @if($help && !$hasError())
        <p class="mt-2 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if($hasError())
        @foreach($errors() as $error)
            <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
        @endforeach
    @endif
</div>
<div>
    <form wire:submit="save" enctype="multipart/form-data" class="space-y-6">

        @if (session()->has('error'))
            <div class="p-4 bg-red-100 text-red-800 rounded-lg shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                            <x-icon name="document-text" class="w-5 h-5 text-primary-600" />
                            Informações Essenciais
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título do Material <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="title" required class="w-full border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors duration-200" placeholder="Ex: Trabalho de prosperidade com Exú Mirim">
                                @error('title') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Material <span class="text-red-500">*</span></label>
                                <select wire:model.live="type" required class="w-full border-gray-300 rounded-lg focus:ring-primary-500 shadow-sm">
                                    <option value="article">Artigo</option>
                                    <option value="ebook">E-book (PDF)</option>
                                    <option value="video">Vídeo</option>
                                </select>
                                @error('type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria <span class="text-red-500">*</span></label>
                                <select wire:model="category_id" required class="w-full border-gray-300 rounded-lg focus:ring-primary-500 shadow-sm">
                                    <option value="">Selecione uma categoria...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                            <x-icon name="cloud-arrow-up" class="w-5 h-5 text-primary-600" />
                            Mídias do Material
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                                                        
                            <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Capa de Apresentação (Thumbnail) <span class="text-red-500">*</span></label>
                                
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-primary-500 transition-colors duration-200 group">
                                    <div class="space-y-1 text-center">
                                        @if ($thumbnail)
                                            <img src="{{ $thumbnail->temporaryUrl() }}" class="mx-auto h-32 object-cover rounded shadow-sm">
                                        @else
                                            <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-primary-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        @endif
                                        <div class="flex text-sm text-gray-600 mt-4 justify-center">
                                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                                <span>Fazer Upload da Capa</span>
                                                <input wire:model="thumbnail" type="file" accept="image/*" class="sr-only">
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, WEBP até 2MB</p>
                                    </div>
                                </div>
                                @error('thumbnail') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            @if(in_array($type, ['ebook', 'video']))
                                <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm"
                                    x-data="{ isUploading: false, progress: 0 }"
                                    x-on:livewire-upload-start="isUploading = true"
                                    x-on:livewire-upload-finish="isUploading = false"
                                    x-on:livewire-upload-error="isUploading = false; alert('Erro no upload.');"
                                    x-on:livewire-upload-progress="progress = $event.detail.progress">
                                    
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        @if($type === 'video')
                                            @if($studyMaterial && $studyMaterial->exists)
                                                Trocar arquivo de Vídeo <span class="text-gray-400 font-normal">(Opcional)</span>
                                            @else
                                                Arquivo de Vídeo (MP4, MOV) <span class="text-red-500">*</span>
                                            @endif
                                        @else
                                            @if($studyMaterial && $studyMaterial->exists)
                                                Trocar arquivo do E-book <span class="text-gray-400 font-normal">(Opcional)</span>
                                            @else
                                                Arquivo do E-book (PDF) <span class="text-red-500">*</span>
                                            @endif
                                        @endif 
                                    </label>
                                    
                                    <div class="mt-1 flex items-center justify-center w-full">
                                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 hover:border-primary-500 transition-colors">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <x-icon name="{{ $type === 'video' ? 'video-camera' : 'document-arrow-up' }}" class="w-8 h-8 text-gray-400 mb-2" />
                                                <p class="mb-2 text-sm text-gray-500 font-semibold">Clique para anexar arquivo</p>
                                                <p class="text-xs text-gray-500">{{ $file ? $file->getClientOriginalName() : 'Nenhum arquivo selecionado' }}</p>
                                            </div>
                                            <input wire:model="file" type="file" class="hidden" accept="{{ $type === 'video' ? 'video/mp4,video/quicktime' : 'application/pdf' }}" />
                                        </label>
                                    </div>

                                    <div x-show="isUploading" x-transition class="mt-4 w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-300 ease-out" x-bind:style="'width: ' + progress + '%'"></div>
                                        <p class="text-xs text-gray-500 mt-1 text-center font-medium" x-text="progress + '%'"></p>
                                    </div>

                                    @error('file') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror


                                </div>

                            @endif

                        </div>
                    </div>
                    
                    <div class="p-6 bg-white border-t border-gray-200 space-y-6">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição Curta</label>
                            <textarea wire:model="description" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 shadow-sm" placeholder="Resumo do material..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Preço (R$) <span class="text-red-500">*</span></label>
                                <input wire:model="price" type="number" step="0.01" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 shadow-sm" placeholder="0.00 para gratuito">
                                <p class="text-xs text-gray-500 mt-1">Deixe 0.00 para ser gratuito.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags (separadas por vírgula)</label>
                                <input wire:model="tags" type="text" class="w-full border-gray-300 rounded-lg focus:ring-primary-500 shadow-sm" placeholder="Ex: umbanda, exú, prosperidade">
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.materials.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 transition-colors">Cancelar</a>
                        
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center px-6 py-2 bg-primary-600 border border-transparent rounded-lg font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors disabled:opacity-50">
                            <span wire:loading.remove>Salvar e Processar Mídia</span>
                            <span wire:loading class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Coluna da Direita: Media & Status (1/3) --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Card de Preview do Vídeo --}}
                @if($type === 'video' && $studyMaterial?->exists)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Preview do Conteúdo</h4>
                    </div>
                    
                    <div class="p-4">
                        @if($studyMaterial->processing_status === 'completed')
                            {{-- Miniatura com Modal --}}
                            <div class="group relative aspect-video rounded-lg overflow-hidden bg-black shadow-inner" style="padding-top: 56.25%;">
                                <iframe 
                                    src="https://iframe.mediadelivery.net/embed/{{ env('BUNNY_STREAM_LIBRARY_ID') }}/{{ $studyMaterial->external_video_id }}?autoplay=false&loop=false&muted=false&preload=true&responsive=true" 
                                    loading="lazy" 
                                    style="border:0;position:absolute;top:0;left:0;width:100%;height:100%;" 
                                    allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;" 
                                    allowfullscreen="true">
                                </iframe>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full uppercase">Pronto para uso</span>
                                <span class="text-[10px] text-gray-400">ID: {{ Str::limit($studyMaterial->external_video_id, 8) }}</span>
                            </div>
                        @elseif($studyMaterial->processing_status === 'processing')
                            <div class="aspect-video bg-gray-900 rounded-lg flex flex-col items-center justify-center text-center p-4">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500 mb-3"></div>
                                <p class="text-[11px] text-gray-400 font-medium">Otimizando para streaming...</p>
                            </div>
                        @else
                            <div class="aspect-video bg-red-50 rounded-lg flex flex-col items-center justify-center text-center p-4 border border-red-100">
                                <x-icon name="exclamation-circle" class="w-8 h-8 text-red-400 mb-2" />
                                <p class="text-[11px] text-red-600 font-bold">Falha no processamento</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                @if($type === 'ebook' && $studyMaterial?->exists)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Visualização do E-book</h4>
                        <div class="flex items-center gap-1">
                            <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                            <span class="text-[10px] font-bold text-gray-400">MODO MINIATURA</span>
                        </div>
                    </div>
                    
                    <div class="p-0 relative"> 
                        @if($studyMaterial->file_path && $studyMaterial->processing_status === 'completed')
                            
                            <div class="relative w-full h-[500px] bg-gray-100 group">
                                
                                <iframe 
                                    id="ebook-sidebar-viewer"
                                    src="https://docs.google.com/viewer?url={{ urlencode($studyMaterial->file_url) }}&embedded=true" 
                                    class="w-full h-full border-0"
                                    frameborder="0">
                                </iframe>
                               
                            </div>

                            <div class="p-4 bg-gray-50 border-t border-gray-100">
                                <div class="flex items-center justify-between text-[10px] text-gray-500">
                                    <span>{{ $studyMaterial->file_name }}</span>
                                    <span>{{ $studyMaterial->file_size_formatted }}</span>
                                </div>
                            </div>

                        @elseif($studyMaterial->processing_status === 'processing')
                            <div class="h-[300px] flex flex-col items-center justify-center text-center p-6 bg-gray-50">
                                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600 mb-4"></div>
                                <p class="text-xs text-gray-500 font-medium">Sincronizando arquivo com o servidor...</p>
                            </div>
                        @else
                            <div class="h-[200px] flex items-center justify-center bg-red-50 p-6">
                                <p class="text-xs text-red-600 font-bold">Arquivo não disponível para prévia.</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Card de Thumbnail (Imagem de Capa) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Capa do Material</h4>
                    <div class="relative aspect-[4/3] bg-gray-100 rounded-lg border-2 border-dashed border-gray-200 overflow-hidden group">
                        @if($thumbnail || $studyMaterial?->thumbnail_path)
                            <img src="{{ $thumbnail ? $thumbnail->temporaryUrl() : $studyMaterial->thumbnail_url }}" class="w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                                <x-icon name="photo" class="w-8 h-8" />
                                <span class="text-[10px] mt-2">Nenhuma capa</span>
                            </div>
                        @endif
                        <input type="file" wire:model="thumbnail" class="absolute inset-0 opacity-0 cursor-pointer">
                    </div>
                    <p class="mt-2 text-[10px] text-gray-400 text-center italic">Clique na imagem para alterar</p>
                </div>
            </div>

        </div>

        
    </form>
</div>
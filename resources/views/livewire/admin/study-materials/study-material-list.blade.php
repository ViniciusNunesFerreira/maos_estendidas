<div @if($materials->whereIn('processing_status', ['pending', 'processing'])->count() > 0) wire:poll.5s @endif>
    <div class="space-y-4">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex flex-wrap gap-3 flex-1">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar material..." class="rounded-lg border-gray-300 text-sm focus:ring-primary-500 w-full max-w-xs">
                
                <select wire:model.live="type" class="rounded-lg border-gray-300 text-sm focus:ring-primary-500">
                    <option value="">Todos os Tipos</option>
                    <option value="video">Vídeo</option>
                    <option value="ebook">E-book</option>
                    <option value="article">Artigo</option>
                </select>

                <select wire:model.live="category_id" class="rounded-lg border-gray-300 text-sm focus:ring-primary-500">
                    <option value="">Todas Categorias</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <a href="{{ route('admin.materials.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition">
                <x-icon name="plus" class="w-4 h-4 mr-2" /> Novo Material
            </a>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Material</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo/Cat</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Preço</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($materials as $material)
                        <tr class="{{ in_array($material->processing_status, ['pending', 'processing']) ? 'bg-gray-50/50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-16 flex-shrink-0">

                                        @if($material->processing_status === 'completed')
                                            <img src="{{ $material->thumbnail_url }}" class="w-16 h-16 rounded-lg object-cover shadow-sm border border-gray-200">
                                        @elseif($material->processing_status === 'failed')
                                            <div class="w-16 h-16 rounded-lg bg-red-100 flex items-center justify-center text-red-500">
                                                <x-icon name="exclamation-circle" class="w-8 h-8" />
                                            </div>
                                        @else
                                            <div class="w-16 h-16 rounded-lg bg-gray-200 animate-pulse flex items-center justify-center">
                                                <x-icon name="cloud-arrow-up" class="w-5 h-5 text-gray-400" />
                                            </div>
                                        @endif

                                
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">{{ $material->title }}</div>
                                        <div class="text-xs text-gray-500">{{ Str::limit($material->description, 40) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ strtoupper($material->type) }}
                                </span>
                                <div class="text-xs text-gray-400 mt-1">{{ $material->category->name ?? 'Sem categoria' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($material->processing_status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-2 h-2 mr-1.5 bg-green-500 rounded-full"></span> Disponível
                                    </span>
                                @elseif(in_array($material->processing_status, ['pending', 'processing']))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 animate-pulse">
                                        <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-amber-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processando
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                       Falhou no Envio
                                    </span>
                                @endif

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $material->is_free ? 'Grátis' : 'R$ ' . number_format($material->price, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    @php $isReady = $material->processing_status === 'completed'; @endphp
                                    
                                    <a href="{{ $isReady ? route('admin.materials.edit', $material) : '#' }}" 
                                       @if(!$isReady) onclick="return false;" @endif
                                       class="{{ $isReady ? 'text-indigo-600 hover:text-indigo-900' : 'text-gray-300 cursor-not-allowed' }}" title="Editar">
                                        <x-icon name="pencil-square" class="w-5 h-5" />
                                    </a>

                                    <button wire:click="delete('{{ $material->id }}')" 
                                            wire:confirm="Tem certeza que deseja remover este material?"
                                            class="text-red-400 hover:text-red-600" title="Excluir">
                                        <x-icon name="trash" class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">Nenhum material encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $materials->links() }}
        </div>
    </div>
</div>
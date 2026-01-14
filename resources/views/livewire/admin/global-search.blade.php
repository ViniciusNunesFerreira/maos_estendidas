<div class="relative flex-1 max-w-2xl mx-6" 
     x-data="{ 
        isOpen: false, 
        selectedIndex: -1,
        
        // Função para selecionar o próximo item
        selectNext() {
            if (this.selectedIndex < $wire.results.length - 1) {
                this.selectedIndex++;
                this.scrollToSelected();
            }
        },
        
        // Função para selecionar o item anterior
        selectPrev() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
                this.scrollToSelected();
            }
        },

        // Função para ir para o link do item selecionado
        selectResult() {
            if (this.selectedIndex >= 0 && $wire.results[this.selectedIndex]) {
                window.location.href = $wire.results[this.selectedIndex].url;
            }
        },

        // Mantém o item selecionado visível no scroll
        scrollToSelected() {
            this.$nextTick(() => {
                const selected = this.$refs.resultsList.children[this.selectedIndex];
                if (selected) {
                    selected.scrollIntoView({ block: 'nearest' });
                }
            });
        }
     }" 
     @click.away="isOpen = false"
     @keydown.escape.window="isOpen = false"
     @keydown.window.prevent.slash="$refs.searchInput.focus(); isOpen = true"
     @keydown.window.prevent.cmd.k="$refs.searchInput.focus(); isOpen = true"
     @keydown.window.prevent.ctrl.k="$refs.searchInput.focus(); isOpen = true">
    
    <div class="relative group">
        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <input 
            type="text" 
            wire:model.live.debounce.300ms="query"
            x-ref="searchInput"
            @focus="isOpen = (query.length >= 2)"
            @input="isOpen = ($event.target.value.length >= 2); selectedIndex = -1"
            @keydown.down.prevent="isOpen = true; selectNext()"
            @keydown.up.prevent="isOpen = true; selectPrev()"
            @keydown.enter.prevent="selectResult()"
            placeholder="Buscar (Pressione '/')" 
            class="w-full pl-10 pr-12 py-2.5 bg-gray-100/80 border-transparent rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200 text-sm font-medium placeholder-gray-400"
            autocomplete="off"
        />

        <!-- Loading Indicator -->
        <div wire:loading class="absolute right-3 top-1/2 -translate-y-1/2">
            <div class="animate-spin h-4 w-4 border-2 border-blue-600 border-t-transparent rounded-full"></div>
        </div>

        <!-- Botão Limpar / Atalho Dica -->
        <div wire:loading.remove class="absolute right-3 top-1/2 -translate-y-1/2">
            @if(strlen($query) > 0)
                <button wire:click="clear" @click="isOpen = false; query = ''" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 8.586L2.929 1.515 1.515 2.929 8.586 10l-7.071 7.071 1.414 1.414L10 11.414l7.071 7.071 1.414-1.414L11.414 10l7.071-7.071-1.414-1.414L10 8.586z"/></svg>
                </button>
            @else
                <div class="hidden md:flex items-center">
                    <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-bold text-gray-400 border border-gray-300 rounded shadow-sm bg-gray-50">/</kbd>
                </div>
            @endif
        </div>
    </div>

    <!-- Dropdown de Resultados -->
    <div x-show="isOpen && query.length >= 2" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="absolute mt-3 w-full bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden ring-1 ring-black ring-opacity-5 z-50">
        
        <div x-ref="resultsList" class="max-h-[450px] overflow-y-auto p-2 space-y-1 custom-scrollbar">
            @forelse($results as $index => $item)
                
                @if($index === 0 || $results[$index-1]['type'] !== $item['type'])
                    <div class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider sticky top-0 bg-white/95 backdrop-blur-sm z-10">
                        {{ $item['type_label'] }}
                    </div>
                @endif

                <a href="{{ $item['url'] }}" 
                   @mouseenter="selectedIndex = {{ $index }}"
                   :class="{ 'bg-blue-50 ring-1 ring-blue-100': selectedIndex === {{ $index }} }"
                   class="flex items-center px-3 py-2.5 rounded-lg group transition-all duration-150 outline-none focus:bg-blue-50">
                    
                    {{-- Ícone Dinâmico --}}
                    <div :class="{ 'bg-blue-100 text-blue-600': selectedIndex === {{ $index }}, 'bg-gray-100 text-gray-400': selectedIndex !== {{ $index }} }"
                         class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center transition-colors">
                        @switch($item['type'])
                            @case('filho')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                @break
                            @case('product')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                @break
                            @case('invoice')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                @break
                            @default
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        @endswitch
                    </div>

                    <div class="ml-3 flex-1 min-w-0">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-blue-700">
                                {{ $item['title'] }}
                            </p>
                            
                            {{-- Badge de Status (se houver cor definida) --}}
                            @if(isset($item['status_color']))
                                <div class="h-2 w-2 rounded-full {{ $item['status_color'] }} ml-2"></div>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 truncate flex items-center mt-0.5">
                            {{ $item['subtitle'] }}
                        </p>
                    </div>

                    {{-- Seta indicativa ao hover/select --}}
                    <div class="ml-2 text-gray-300" :class="{ 'text-blue-400': selectedIndex === {{ $index }} }">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @empty
                <div class="py-8 text-center px-4">
                    <div class="bg-gray-50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg>
                    </div>
                    <p class="text-sm text-gray-900 font-medium">Nenhum resultado encontrado</p>
                    <p class="text-xs text-gray-500 mt-1">Não encontramos nada para "<span class="font-semibold text-gray-700">{{ $query }}</span>".</p>
                </div>
            @endforelse
            
            {{-- Rodapé Informativo --}}
            @if(count($results) > 0)
                <div class="pt-2 mt-2 border-t border-gray-100 flex justify-between items-center px-2">
                    <span class="text-[10px] text-gray-400">Mostrando {{ count($results) }} resultados</span>
                    <div class="hidden sm:flex space-x-2 text-[10px] text-gray-400 items-center">
                        <span class="flex items-center"><kbd class="font-sans border border-gray-200 rounded px-1 mr-1">↑↓</kbd> Navegar</span>
                        <span class="flex items-center"><kbd class="font-sans border border-gray-200 rounded px-1 mr-1">Enter</kbd> Selecionar</span>
                        <span class="flex items-center"><kbd class="font-sans border border-gray-200 rounded px-1 mr-1">Esc</kbd> Fechar</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
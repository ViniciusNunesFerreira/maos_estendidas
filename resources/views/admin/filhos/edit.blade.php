<x-layouts.admin title="Editar Filho">
    <div class="space-y-6">
        {{-- Breadcrumbs --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                        <x-icon name="home" class="w-4 h-4" />
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <x-icon name="chevron-right" class="w-4 h-4 text-gray-400" />
                        <a href="{{ route('admin.filhos.index') }}" class="ml-1 text-sm text-gray-500 hover:text-gray-700">Filhos</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <x-icon name="chevron-right" class="w-4 h-4 text-gray-400" />
                        <span class="ml-1 text-sm text-gray-900 font-medium">Editar</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                @if($filho->photo_url)
                    <img src="{{ Storage::url($filho->photo_url) }}" alt="{{ $filho->fullname }}" class="w-16 h-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
                        <span class="text-2xl font-semibold text-primary-600">{{ substr($filho->fullname, 0, 1) }}</span>
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Editar Filho</h1>
                    <p class="text-sm text-gray-500">{{ $filho->fullname }}</p>
                </div>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.filhos.show', $filho) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancelar
                </a>
            </div>
        </div>

        {{-- Componente Livewire com o formulário --}}
        <livewire:admin.filhos.filho-form :filho="$filho" />
    </div>
</x-layouts.admin>
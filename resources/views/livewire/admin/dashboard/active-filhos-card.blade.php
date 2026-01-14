{{-- resources/views/livewire/admin/dashboard/active-filhos-card.blade.php --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Filhos Ativos</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($activeFilhos) }}</p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <x-icon name="users" class="w-6 h-6 text-green-600" />
        </div>
    </div>
    
    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
        @if($pendingApproval > 0)
            <a href="{{ route('admin.filhos.approval') }}" class="flex items-center space-x-2 text-yellow-600 hover:text-yellow-700">
                <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                <span>{{ $pendingApproval }} aguardando</span>
            </a>
        @else
            <div class="flex items-center space-x-2 text-gray-400">
                <span class="w-2 h-2 bg-gray-300 rounded-full"></span>
                <span>0 aguardando</span>
            </div>
        @endif
        
        @if($blockedFilhos > 0)
            <div class="flex items-center space-x-2 text-red-600">
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                <span>{{ $blockedFilhos }} bloqueados</span>
            </div>
        @else
            <div class="flex items-center space-x-2 text-gray-400">
                <span class="w-2 h-2 bg-gray-300 rounded-full"></span>
                <span>0 bloqueados</span>
            </div>
        @endif
    </div>
</div>
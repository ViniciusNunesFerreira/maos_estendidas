{{-- resources/views/livewire/admin/dashboard/overdue-invoices-card.blade.php --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Faturas Vencidas</p>
            <p class="mt-1 text-3xl font-bold text-red-600">{{ $overdueCount }}</p>
            <p class="mt-1 text-sm text-gray-600">
                R$ {{ number_format($overdueAmount, 2, ',', '.') }}
            </p>
        </div>
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <x-icon name="exclamation-triangle" class="w-6 h-6 text-red-600" />
        </div>
    </div>
    
    <div class="mt-4 flex items-center justify-between text-sm">
        @if($trend > 0)
            <div class="flex items-center gap-1 text-red-600">
                <x-icon name="trending-up" class="w-4 h-4" />
                <span class="font-medium">+{{ abs($trend) }}%</span>
                <span class="text-gray-400">vs mês anterior</span>
            </div>
        @elseif($trend < 0)
            <div class="flex items-center gap-1 text-green-600">
                <x-icon name="trending-down" class="w-4 h-4" />
                <span class="font-medium">-{{ abs($trend) }}%</span>
                <span class="text-gray-400">vs mês anterior</span>
            </div>
        @else
            <div class="flex items-center gap-1 text-gray-500">
                <x-icon name="minus" class="w-4 h-4" />
                <span>Sem mudança</span>
            </div>
        @endif
        
        @if($dueThisWeek > 0)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                {{ $dueThisWeek }} vencem esta semana
            </span>
        @endif
    </div>
</div>
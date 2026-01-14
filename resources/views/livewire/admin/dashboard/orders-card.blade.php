<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Pedidos Hoje</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($todayOrders) }}</p>
        </div>
        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <x-icon name="shopping-bag" class="w-6 h-6 text-blue-600" />
        </div>
    </div>
    
    <div class="mt-4 flex items-center justify-between text-sm">
        <div class="flex items-center space-x-1">
            @if($trend > 0)
                <x-icon name="trending-up" class="w-4 h-4 text-green-500" />
                <span class="text-green-600 font-medium">+{{ $trend }}%</span>
            @elseif($trend < 0)
                <x-icon name="trending-down" class="w-4 h-4 text-red-500" />
                <span class="text-red-600 font-medium">{{ $trend }}%</span>
            @else
                <x-icon name="minus" class="w-4 h-4 text-gray-400" />
                <span class="text-gray-500">0%</span>
            @endif
            <span class="text-gray-400">vs ontem</span>
        </div>
        
        @if($pendingOrders > 0)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                {{ $pendingOrders }} pendentes
            </span>
        @endif
    </div>
</div>
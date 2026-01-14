{{-- resources/views/livewire/admin/dashboard/top-products.blade.php --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Produtos Mais Vendidos</h3>
        <select wire:model.live="period" class="text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="7">Últimos 7 dias</option>
            <option value="30">Últimos 30 dias</option>
            <option value="90">Últimos 90 dias</option>
        </select>
    </div>
    
    <div class="space-y-4">
        @forelse($products as $index => $product)
            <div class="flex items-center space-x-4">
                <span class="w-6 text-center text-sm font-bold text-gray-400">{{ $index + 1 }}</span>
                
                @if($product['image_url'])
                    <img src="{{ Storage::url($product['image_url']) }}" alt="{{ $product['name'] }}" class="w-10 h-10 rounded-lg object-cover">
                @else
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <x-icon name="package" class="w-5 h-5 text-gray-400" />
                    </div>
                @endif
                
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $product['name'] }}</p>
                    <p class="text-xs text-gray-500">{{ $product['quantity_sold'] }} vendidos</p>
                </div>
                
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900">R$ {{ number_format($product['total_revenue'], 2, ',', '.') }}</p>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-500">
                <x-icon name="package" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                <p>Nenhuma venda no período</p>
            </div>
        @endforelse
    </div>
    
    @if(count($products) > 0)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('admin.reports.products') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                Ver relatório completo →
            </a>
        </div>
    @endif
</div>
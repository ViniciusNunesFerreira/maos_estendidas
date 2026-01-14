{{-- resources/views/admin/orders/show.blade.php --}}
<x-layouts.admin title="Detalhes do Pedido">
    <div class="max-w-4xl mx-auto">
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
                        <a href="{{ route('admin.orders.index') }}" class="ml-1 text-sm text-gray-500 hover:text-gray-700">Pedidos</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <x-icon name="chevron-right" class="w-4 h-4 text-gray-400" />
                        <span class="ml-1 text-sm text-gray-900 font-medium">#{{ $order->order_number }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Detalhes do Pedido --}}
        <livewire:admin.orders.order-details :order="$order" />
    </div>
</x-layouts.admin>
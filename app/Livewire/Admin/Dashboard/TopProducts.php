<?php

namespace App\Livewire\Admin\Dashboard;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TopProducts extends Component
{
    public string $period = '7';
    public array $products = [];

    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function updatedPeriod(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->products = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', now()->subDays((int) $this->period))
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'products.id',
                'products.name',
                'products.image_url'
            )
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.image_url')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image_url' => $p->image_url,
                'quantity_sold' => (int) $p->quantity_sold,
                'total_revenue' => (float) $p->total_revenue,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.dashboard.top-products');
    }
}
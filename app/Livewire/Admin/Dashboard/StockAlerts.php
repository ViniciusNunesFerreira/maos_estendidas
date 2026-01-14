<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Product;
use Livewire\Component;

class StockAlerts extends Component
{
    public array $lowStockProducts = [];
    public array $outOfStockProducts = [];
    public int $totalAlerts = 0;

    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        // Produtos com estoque baixo
        $this->lowStockProducts = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->where('stock_quantity', '>', 0)
            ->select('id', 'name', 'sku', 'stock_quantity', 'min_stock_alert')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock_quantity,
                'stock_min' => $p->min_stock_alert,
                'percentage' => $p->min_stock_alert > 0 
                    ? round(($p->stock_quantity / $p->min_stock_alert) * 100) 
                    : 0,
            ])
            ->toArray();

        // Produtos sem estoque
        $this->outOfStockProducts = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', 0)
            ->select('id', 'name', 'sku')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
            ])
            ->toArray();

        $this->totalAlerts = count($this->lowStockProducts) + count($this->outOfStockProducts);
    }

    public function render()
    {
        return view('livewire.admin.dashboard.stock-alerts');
    }
}
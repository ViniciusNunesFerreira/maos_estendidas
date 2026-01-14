<?php

namespace App\Livewire\Admin\Stock;

use App\Models\Product;
use App\Models\StockMovement;
use Livewire\Component;
use Livewire\WithPagination;

class StockList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';
    public string $locationFilter = '';

    protected $queryString = ['search', 'statusFilter', 'categoryFilter', 'locationFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->with('category')
            ->when($this->search, fn($q) => 
                $q->where('name', 'ilike', "%{$this->search}%")
                  ->orWhere('sku', 'ilike', "%{$this->search}%")
            )
            ->when($this->statusFilter === 'low', fn($q) => 
                $q->whereColumn('stock_quantity', '<=', 'min_stock_alert')->where('stock_quantity', '>', 0)
            )
            ->when($this->statusFilter === 'out', fn($q) => $q->where('stock_quantity', 0))
            ->when($this->statusFilter === 'ok', fn($q) => $q->whereColumn('stock_quantity', '>', 'min_stock_alert'))
            ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->locationFilter, fn($q) => $q->where('location', $this->locationFilter))
            ->orderBy('stock_quantity')
            ->paginate(20);

        $stats = [
            'total_products' => Product::where('is_active', true)->count(),
            'out_of_stock' => Product::where('is_active', true)->where('stock_quantity', 0)->count(),
            'low_stock' => Product::where('is_active', true)->whereColumn('stock_quantity', '<=', 'min_stock_alert')->where('stock_quantity', '>', 0)->count(),
            'total_value' => Product::where('is_active', true)->selectRaw('SUM(stock_quantity * cost_price) as total')->value('total') ?? 0,
        ];

        return view('livewire.admin.stock.stock-list', [
            'products' => $products,
            'stats' => $stats,
            'categories' => \App\Models\Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
<?php

namespace App\Livewire\Admin\Stock;

use App\Models\Product;
use App\Services\StockService;
use Livewire\Component;

class StockEntry extends Component
{
    public ?string $productId = null;
    public ?Product $selectedProduct = null;
    public int $quantity = 1;
    public string $reason = ''; // Usado para Observações
    public string $invoice_number = '';
    public string $supplier = '';
    public ?float $unit_cost = null;
    
    public string $searchProduct = '';
    public array $searchResults = [];

    protected $rules = [
        'productId' => 'required|uuid|exists:products,id',
        'quantity' => 'required|integer|min:1|max:10000',
        'reason' => 'required|string|min:3|max:500',
        'invoice_number' => 'nullable|string|max:50',
        'supplier' => 'nullable|string|max:255',
        'unit_cost' => 'nullable|numeric|min:0',
    ];

    public function updatedSearchProduct(): void
    {
        if (strlen($this->searchProduct) >= 2) {
            $this->searchResults = Product::query()
                ->where('is_active', true)
                ->where(fn($q) => 
                    $q->where('name', 'ilike', "%{$this->searchProduct}%")
                      ->orWhere('sku', 'ilike', "%{$this->searchProduct}%")
                      ->orWhere('barcode', $this->searchProduct)
                )
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock_quantity' => $p->stock_quantity,
                    'cost_price' => $p->cost_price,
                ])
                ->toArray();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectProduct(string $id): void
    {
        $this->selectedProduct = Product::find($id);
        $this->productId = $id;
        $this->unit_cost = $this->selectedProduct?->cost_price;
        $this->searchProduct = $this->selectedProduct?->name ?? '';
        $this->searchResults = [];
    }

    public function clearProduct(): void
    {
        $this->reset(['productId', 'selectedProduct', 'searchProduct', 'unit_cost', 'searchResults']);
    }

    public function save(): void
    {
        $this->validate();

        $stockService = app(StockService::class);

        $stockService->entry(
            product: $this->selectedProduct,
            quantity: $this->quantity,
            reason: $this->reason,
            userId: auth()->id(),
            metadata: [
                'invoice_number' => $this->invoice_number,
                'supplier' => $this->supplier,
                'unit_cost' => $this->unit_cost,
            ]
        );

        if ($this->unit_cost && $this->unit_cost !== $this->selectedProduct->cost_price) {
            $this->selectedProduct->update(['cost_price' => $this->unit_cost]);
        }

        session()->flash('message', "Entrada de {$this->quantity} unidade(s) registrada!");
        $this->reset(['productId', 'selectedProduct', 'quantity', 'reason', 'invoice_number', 'supplier', 'unit_cost', 'searchProduct']);
    }

    public function render()
    {
        return view('livewire.admin.stock.stock-entry');
    }
}
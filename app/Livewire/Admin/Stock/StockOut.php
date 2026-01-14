<?php

namespace App\Livewire\Admin\Stock;

use App\Models\Product;
use App\Services\StockService;
use Livewire\Component;

class StockOut extends Component
{
    public ?string $productId = null;
    public ?Product $selectedProduct = null;
    public int $quantity = 1;
    public string $reason = '';
    public string $type = 'adjustment';
    
    public string $searchProduct = '';
    public array $searchResults = [];

    protected $rules = [
        'productId' => 'required|uuid|exists:products,id',
        'quantity' => 'required|integer|min:1',
        'reason' => 'required|string|min:3|max:500',
        'type' => 'required|in:adjustment,loss,out,return',
    ];

    public function updatedSearchProduct(): void
    {
        if (strlen($this->searchProduct) >= 2) {
            $this->searchResults = Product::query()
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
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
        $this->searchProduct = $this->selectedProduct?->name ?? '';
        $this->searchResults = [];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->quantity > ($this->selectedProduct->stock_quantity ?? 0)) {
            $this->addError('quantity', 'Quantidade maior que o estoque disponível.');
            return;
        }

        $typeLabels = [
            'adjustment' => 'Ajuste',
            'loss' => 'Perda|Vencido|Danificado',
            'out' => 'Venda',
            'return' => 'Devolução',
        ];

        app(StockService::class)->out(
            product: $this->selectedProduct,
            quantity: $this->quantity,
            reason: "[{$typeLabels[$this->type]}] {$this->reason}",
            userId: auth()->id(),
            type: $this->type,
        );

        session()->flash('message', "Saída registrada com sucesso!");
        $this->reset(['productId', 'selectedProduct', 'quantity', 'reason', 'type', 'searchProduct']);
    }

    public function render()
    {
        return view('livewire.admin.stock.stock-out');
    }
}
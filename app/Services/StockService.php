<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Entrada de estoque
     */
    public function entry(
        Product $product,
        int $quantity,
        string $reason,
        $userId = null,
        array $metadata = []
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $reason, $userId, $metadata) {
            $stockBefore = $product->stock_quantity;
            $product->skipStockObserver();
            $product->incrementStock($quantity);
            $product->refresh();

            \Log::info('id do user que veio: '.$userId);
            \Log::info('id do user auth'. auth()->id());

            

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => StockMovement::TYPE_ENTRY,
                'quantity' => $quantity,
                'quantity_before' => $stockBefore,
                'quantity_after' => $product->stock_quantity,
                'reason' => $reason,
                'unit_cost' => $metadata['unit_cost'] ?? null,
                'invoice_number' => $metadata['invoice_number'] ?? null,
                'supplier' => $metadata['supplier'] ?? null,
                'user_id' => $userId ?? auth()->id(),
                'reference' => 'ADJUSTMENT_ENTRY',
            ]);
        });
    }

    /**
     * Saída de estoque (baixa manual)
     */
    public function out(
        Product $product,
        int $quantity,
        string $reason,
        $type = null,
        $userId = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $reason, $userId, $type) {
            $stockBefore = $product->stock_quantity;
            $product->skipStockObserver();
            $product->decrementStock($quantity);
            $product->refresh();

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => $type ?? 'adjustment',
                'quantity' => $quantity,
                'quantity_before' => $stockBefore,
                'quantity_after' => $product->stock_quantity,
                'reason' => $reason,
                'user_id' => $userId ?? auth()->id(),
                'reference' => 'ADJUSTMENT_OUT',
            ]);
        });
    }

    /**
     * Incrementar estoque (usado em cancelamentos)
     */
    public function increment(
        Product $product,
        int $quantity,
        string $reason,
        ?string $orderId = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $reason, $orderId) {
            $stockBefore = $product->stock_quantity;
            
            $product->incrementStock($quantity);
            $product->refresh();

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock_quantity,
                'reason' => $reason,
                'order_id' => $orderId,
                'user_id' => auth()->id(),
                'reference' => 'CANCEL',
            ]);
        });
    }

    /**
     * Decrementar estoque (usado em vendas)
     */
    public function decrement(
        Product $product,
        int $quantity,
        string $reason,
        ?string $orderId = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $reason, $orderId) {
            $stockBefore = $product->stock_quantity;
            
            $product->decrementStock($quantity);
            $product->refresh();

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'sale',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock_quantity,
                'reason' => $reason,
                'order_id' => $orderId,
                'user_id' => auth()->id(),
                'reference' => 'SALE',
            ]);
        });
    }

    /**
     * Ajustar estoque (inventário)
     */
    public function adjust(
        Product $product,
        int $newQuantity,
        string $reason,
        ?int $userId = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $newQuantity, $reason, $userId) {
            $stockBefore = $product->stock;
            $difference = $newQuantity - $stockBefore;
            
            $product->update(['stock' => $newQuantity]);

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'reason' => "[Inventário] {$reason}",
                'created_by_user_id' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Verificar disponibilidade de estoque
     */
    public function checkAvailability(Product $product, int $quantity): bool
    {
        return $product->stock >= $quantity;
    }

    /**
     * Obter produtos com estoque baixo
     */
    public function getLowStockProducts(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'stock_min')
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->limit($limit)
            ->get();
    }

    /**
     * Obter produtos sem estoque
     */
    public function getOutOfStockProducts(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('stock', 0)
            ->limit($limit)
            ->get();
    }

    /**
     * Obter histórico de movimentações
     */
    public function getMovementHistory(
        ?Product $product = null,
        ?string $type = null,
        int $limit = 50
    ): \Illuminate\Database\Eloquent\Collection {
        $query = StockMovement::query()
            ->with(['product', 'createdByUser'])
            ->orderByDesc('created_at');

        if ($product) {
            $query->where('product_id', $product->id);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Calcular valor total do estoque
     */
    public function calculateTotalStockValue(): float
    {
        return Product::query()
            ->where('is_active', true)
            ->selectRaw('SUM(stock * COALESCE(cost_price, 0)) as total')
            ->value('total') ?? 0;
    }

    /**
     * Obter resumo de estoque
     */
    public function getStockSummary(): array
    {
        return [
            'total_products' => Product::where('is_active', true)->count(),
            'total_items' => Product::where('is_active', true)->sum('stock'),
            'out_of_stock' => Product::where('is_active', true)->where('stock', 0)->count(),
            'low_stock' => Product::where('is_active', true)
                ->whereColumn('stock', '<=', 'stock_min')
                ->where('stock', '>', 0)
                ->count(),
            'total_value' => $this->calculateTotalStockValue(),
        ];
    }
}
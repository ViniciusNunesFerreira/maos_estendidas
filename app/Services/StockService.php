<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
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
                'type' => $type ?? StockMovement::TYPE_ADJUSTMENT,
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

    /**
     * Reserva o estoque: Apenas "bloqueia" o saldo para venda
     */
    public function reserveStock(Order $order)
    {
       
        DB::transaction(function () use ($order) {

            $order->load('items');

            if ($order->items->isEmpty()) {
                return;
            }

            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                    if ($product->available_stock < $item->quantity) {
                        throw new \Exception("Estoque insuficiente para o produto: {$product->name}");
                    }

                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'order_id' => $order->id,
                            'user_id' => $order->user_id ?? auth()->id(),
                            'type' => StockMovement::TYPE_RESERVE,
                            'quantity' => $item->quantity,
                            'quantity_before' => $product->stock_quantity,
                            'quantity_after' => $product->stock_quantity, 
                            'reason' => "Reserva de pedido #{$order->order_number}",
                            'reference' => 'TYPE_RESERVE',
                        ]);

            }
        });
    }

    /**
     * Efetiva a venda: Transforma a reserva em saída real (decrementa stock_quantity)
     */
    public function confirmStockExit(Order $order)
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                // Remove a reserva prévia
                StockMovement::where('order_id', $order->id)
                    ->where('product_id', $item->product_id)
                    ->where('type', 'reserve')
                    ->delete();

                $stockBefore = $product->stock_quantity;
                $product->decrement('stock_quantity', $item->quantity);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'user_id' => $order->user_id ?? auth()->id(),
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => $item->quantity,
                    'quantity_before' => $stockBefore,
                    'quantity_after' => $product->stock_quantity,
                    'reason' => "Venda confirmada #{$order->order_number}",
                ]);
            }
        });
    }

    /**
     * RESTAURAÇÃO: Para cancelamentos ou expiração
     */
    public function rollbackReservation(Order $order)
    {
        // Apenas deleta as reservas, o available_stock volta ao normal automaticamente
        StockMovement::where('order_id', $order->id)
            ->where('type', 'reserve')
            ->delete();
    }

    /**
     * DEVOLUÇÃO: Retorna itens de um pedido cancelado ao estoque físico.
     * Usado quando o pedido já havia saído do status 'pending'.
     */
    public function returnToPhysicalStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Carregamos os itens do pedido
            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                // Se o produto não controla estoque, pulamos
                if (!$product->track_stock) continue;

                $stockBefore = $product->stock_quantity;
                
                // Incrementa o estoque físico
               // $product->increment('stock_quantity', $item->quantity);

                if ($product->requires_preparation) {
                    // Se for um item de cozinha já preparado, talvez não deva voltar ao estoque
                    // Registramos apenas como 'loss' (perda) em vez de 'return'
                    $type = StockMovement::TYPE_LOSS; 
                } else {
                    $type = StockMovement::TYPE_RETURN;
                    $product->increment('stock_quantity', $item->quantity);
                }

                // Registra a movimentação de devolução/estorno
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'order_id'   => $order->id,
                    'user_id'    => auth()->id() ?? $order->user_id,
                    'type'       => $type,
                    'quantity'   => $item->quantity,
                    'quantity_before' => $stockBefore,
                    'quantity_after'  => $product->stock_quantity,
                    'reason'     => "Estorno de estoque: Pedido #{$order->order_number} cancelado.",
                    'metadata'   => [
                        'previous_status' => $order->getOriginal('status'),
                        'cancelled_at'    => now()->toDateTimeString()
                    ]
                ]);
            }
        });
    }



}
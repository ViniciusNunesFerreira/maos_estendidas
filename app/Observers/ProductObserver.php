<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Registrar movimento de estoque inicial se houver
        if ($product->stock_quantity > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => StockMovement::TYPE_ENTRY,
                'quantity' => $product->stock_quantity,
                'quantity_before' => 0,
                'quantity_after' => $product->stock_quantity,
                'reason' => 'Estoque inicial do produto',
                'reference' => 'INITIAL',
            ]);

            Log::info('Product created with initial stock', [
                'product_id' => $product->id,
                'stock_quantity' => $product->stock_quantity,
            ]);
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Se o estoque foi alterado manualmente
        if ($product->isDirty('stock_quantity')) {
            $oldQuantity = $product->getOriginal('stock_quantity');
            $newQuantity = $product->stock_quantity;
            $difference = $newQuantity - $oldQuantity;

            // Apenas registrar se não foi através do StockService
            // StockService usa skipStockObserver() para evitar duplicação
            if (!($product->_skip_stock_observer ?? false)) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'type' => StockMovement::TYPE_ADJUSTMENT,
                    'quantity' => abs($difference),
                    'quantity_before' => $oldQuantity,
                    'quantity_after' => $newQuantity,
                    'reason' => 'Ajuste manual de estoque via atualização do produto',
                    'reference' => 'MANUAL_ADJUSTMENT',
                ]);

                Log::info('Product stock adjusted manually', [
                    'product_id' => $product->id,
                    'from' => $oldQuantity,
                    'to' => $newQuantity,
                    'difference' => $difference,
                ]);
            }
        }

        // Se o status mudou para inactive
        if ($product->isDirty('status') && $product->status === 'inactive') {
            Log::info('Product deactivated', [
                'product_id' => $product->id,
                'stock_quantity' => $product->stock_quantity,
                'user_id' => Auth::id(),
            ]);
        }

        // Se o produto mudou de tipo (loja <-> cantina)
        if ($product->isDirty('type')) {
            Log::info('Product type changed', [
                'product_id' => $product->id,
                'from' => $product->getOriginal('type'),
                'to' => $product->type,
            ]);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Soft delete mantém registros de estoque
        Log::warning('Product soft deleted', [
            'product_id' => $product->id,
            'stock_quantity' => $product->stock_quantity,
        ]);
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        Log::info('Product restored', [
            'product_id' => $product->id,
            'stock_quantity' => $product->stock_quantity,
        ]);
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        Log::critical('Product force deleted - stock movements orphaned', [
            'product_id' => $product->id,
        ]);
    }
}
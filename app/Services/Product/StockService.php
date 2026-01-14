<?php
// app/Services/Product/StockService.php

namespace App\Services\Product;

use App\Events\StockUpdated;
use App\Models\Product;

class StockService
{
    public function increment(Product $product, int $quantity): void
    {
        if (!$product->track_stock) {
            return;
        }

        $oldQuantity = $product->stock_quantity;
        $product->incrementStock($quantity);
        
        event(new StockUpdated($product, $oldQuantity, $product->fresh()->stock_quantity));
    }

    public function decrement(Product $product, int $quantity): void
    {
        if (!$product->track_stock) {
            return;
        }

        if (!$product->hasStock($quantity)) {
            throw new \App\Exceptions\InsufficientStockException(
                "Estoque insuficiente para {$product->name}"
            );
        }

        $oldQuantity = $product->stock_quantity;
        $product->decrementStock($quantity);
        
        event(new StockUpdated($product, $oldQuantity, $product->fresh()->stock_quantity));
    }

    public function adjust(Product $product, int $newQuantity, string $reason): void
    {
        if (!$product->track_stock) {
            return;
        }

        $oldQuantity = $product->stock_quantity;
        
        $product->update(['stock_quantity' => $newQuantity]);
        
        // Log adjustment
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'action' => 'stock_adjustment',
            'old_values' => ['stock_quantity' => $oldQuantity],
            'new_values' => ['stock_quantity' => $newQuantity, 'reason' => $reason],
            'ip_address' => request()->ip(),
        ]);
        
        event(new StockUpdated($product, $oldQuantity, $newQuantity));
    }
}
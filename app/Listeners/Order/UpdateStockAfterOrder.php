<?php
// app/Listeners/Order/UpdateStockAfterOrder.php

namespace App\Listeners\Order;

use App\Events\Order\OrderCreated;
use App\Services\StockService;

class UpdateStockAfterOrder
{
    public function __construct(private readonly StockService $stockService) {}

    public function handle(OrderCreated $event): void
    {
        foreach ($event->order->items as $item) {
            $this->stockService->decreaseStock(
                $item->product_id,
                $item->quantity,
                'sale',
                'Order #' . $event->order->order_number
            );
        }
    }
}
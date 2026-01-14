<?php
// app/Observers/OrderObserver.php

namespace App\Observers;

use App\Events\Order\OrderCreated;
use App\Events\Order\OrderCompleted;
use App\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        event(new OrderCreated($order));
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && $order->status === 'completed') {
            event(new OrderCompleted($order));
        }
    }
}
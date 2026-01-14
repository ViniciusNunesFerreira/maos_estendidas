<?php
// app/Jobs/ProcessOfflineOrdersJob.php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOfflineOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $offlineOrders) {}

    public function handle(OrderService $orderService): void
    {
        foreach ($this->offlineOrders as $orderData) {
            try {
                $orderService->processOfflineOrder($orderData);
            } catch (\Exception $e) {
                \Log::error('Failed to process offline order', [
                    'order_data' => $orderData,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

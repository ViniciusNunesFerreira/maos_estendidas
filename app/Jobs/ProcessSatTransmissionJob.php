<?php
// app/Jobs/ProcessSatTransmissionJob.php

namespace App\Jobs;

use App\Models\Order;
use App\Services\FiscalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSatTransmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $orderId) {}

    public function handle(FiscalService $fiscalService): void
    {
        $order = Order::with(['items.product', 'filho'])->find($this->orderId);

        if (!$order) {
            return;
        }

        $fiscalService->generateSatDocument($order);
    }
}

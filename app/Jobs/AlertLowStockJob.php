<?php
// app/Jobs/AlertLowStockJob.php

namespace App\Jobs;

use App\Models\Product;
use App\Notifications\Admin\LowStockAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class AlertLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'min_stock_quantity')
            ->where('status', 'active')
            ->get();

        if ($lowStockProducts->isNotEmpty()) {
            // Notify admins
            $admins = \App\Models\User::role('admin')->get();
            Notification::send($admins, new LowStockAlertNotification($lowStockProducts));
        }
    }
}
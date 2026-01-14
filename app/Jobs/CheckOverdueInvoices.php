<?php
// app/Jobs/CheckOverdueInvoices.php

namespace App\Jobs;

use App\Services\InvoiceService;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckOverdueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        InvoiceService $invoiceService,
        SubscriptionService $subscriptionService
    ): void {
        Log::info('Verificando faturas vencidas...');
        
        $consumptionOverdue = $invoiceService->checkOverdueInvoices();
        $subscriptionOverdue = $subscriptionService->checkOverdueInvoices();
        
        Log::info("Faturas marcadas como vencidas - Consumo: {$consumptionOverdue}, Assinatura: {$subscriptionOverdue}");
    }
}
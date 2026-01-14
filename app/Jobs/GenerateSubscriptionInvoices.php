<?php
// app/Jobs/GenerateSubscriptionInvoices.php

namespace App\Jobs;

use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSubscriptionInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SubscriptionService $subscriptionService): void
    {
        Log::info('Iniciando geração de faturas de assinatura...');
        
        $invoices = $subscriptionService->generatePendingInvoices();
        
        Log::info("Faturas de assinatura geradas: {$invoices->count()}");
    }
}

<?php
// app/Jobs/GenerateMonthlyInvoices.php

namespace App\Jobs;

use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InvoiceService $invoiceService): void
    {
        Log::info('Iniciando geração de faturas de consumo...');
        
        $invoices = $invoiceService->generateMonthlyInvoices();
        
        Log::info("Faturas de consumo geradas: {$invoices->count()}");
    }
}
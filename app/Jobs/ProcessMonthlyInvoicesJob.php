<?php
// app/Jobs/ProcessMonthlyInvoicesJob.php

namespace App\Jobs;

use App\Models\Filho;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly int $referenceMonth,
        public readonly int $referenceYear
    ) {}

    public function handle(InvoiceService $invoiceService): void
    {
        Log::info('Starting monthly invoice generation', [
            'month' => $this->referenceMonth,
            'year' => $this->referenceYear
        ]);

        $filhos = Filho::where('status', 'active')
            ->whereHas('orders', function ($query) {
                $query->where('status', 'completed')
                    ->whereMonth('created_at', $this->referenceMonth)
                    ->whereYear('created_at', $this->referenceYear);
            })
            ->get();

        foreach ($filhos as $filho) {
            try {
                $invoice = $invoiceService->generateMonthlyInvoice(
                    $filho->id,
                    $this->referenceMonth,
                    $this->referenceYear
                );

                Log::info('Invoice generated successfully', [
                    'filho_id' => $filho->id,
                    'invoice_id' => $invoice->id
                ]);

                // Dispatch notification job
                SendInvoiceNotificationJob::dispatch($invoice->id);
            } catch (\Exception $e) {
                Log::error('Failed to generate invoice', [
                    'filho_id' => $filho->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Monthly invoice generation completed');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessMonthlyInvoicesJob failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
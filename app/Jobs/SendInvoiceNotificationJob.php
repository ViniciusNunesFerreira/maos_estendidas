<?php
// app/Jobs/SendInvoiceNotificationJob.php

namespace App\Jobs;

use App\Models\Invoice;
use App\Notifications\Filho\InvoiceGeneratedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvoiceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $invoiceId) {}

    public function handle(): void
    {
        $invoice = Invoice::with('filho')->find($this->invoiceId);

        if (!$invoice) {
            return;
        }

        $invoice->filho->notify(new InvoiceGeneratedNotification($invoice));
    }
}
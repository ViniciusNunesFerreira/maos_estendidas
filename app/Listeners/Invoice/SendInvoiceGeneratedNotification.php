<?php
// app/Listeners/Invoice/SendInvoiceGeneratedNotification.php

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoiceGenerated;
use App\Jobs\SendInvoiceNotificationJob;

class SendInvoiceGeneratedNotification
{
    public function handle(InvoiceGenerated $event): void
    {
        SendInvoiceNotificationJob::dispatch($event->invoice->id);
    }
}
<?php
// app/Observers/InvoiceObserver.php

namespace App\Observers;

use App\Events\Invoice\InvoiceGenerated;
use App\Events\Invoice\InvoicePaid;
use App\Models\Invoice;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        event(new InvoiceGenerated($invoice));
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->status === 'paid') {
            event(new InvoicePaid($invoice));
        }
    }
}
<?php
// app/Events/Invoice/InvoicePaid.php

namespace App\Events\Invoice;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}
}
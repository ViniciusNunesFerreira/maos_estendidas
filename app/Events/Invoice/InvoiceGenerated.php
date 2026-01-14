<?php
// app/Events/Invoice/InvoiceGenerated.php

namespace App\Events\Invoice;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}
}
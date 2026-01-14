<?php
// app/Jobs/SendSubscriptionInvoiceNotification.php

namespace App\Jobs;

use App\Models\SubscriptionInvoice;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSubscriptionInvoiceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SubscriptionInvoice $invoice
    ) {}

    public function handle(SmsService $smsService): void
    {
        $filho = $this->invoice->filho;
        
        if (!$filho || !$filho->phone) {
            return;
        }
        
        $message = sprintf(
            'Mãos Estendidas: Sua mensalidade de %s no valor de R$ %s vence em %s.',
            $this->invoice->period_start->format('M/Y'),
            number_format($this->invoice->total, 2, ',', '.'),
            $this->invoice->due_date->format('d/m')
        );
        
        $smsService->send($filho->phone, $message);
        
        $this->invoice->update(['notification_sent' => true]);
        
        Log::info("Notificação de mensalidade enviada", [
            'invoice_id' => $this->invoice->id,
            'filho_id' => $filho->id,
        ]);
    }
}

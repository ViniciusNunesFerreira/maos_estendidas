<?php
// app/Jobs/SendInvoiceNotification.php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function handle(SmsService $smsService): void
    {
        $filho = $this->invoice->filho;
        
        if (!$filho) {
            return;
        }
        
        // Enviar SMS
        if ($filho->phone) {
            $message = sprintf(
                'Mãos Estendidas: Sua fatura %s no valor de R$ %s vence em %s. Acesse o app para mais detalhes.',
                $this->invoice->invoice_number,
                number_format($this->invoice->total_amount, 2, ',', '.'),
                $this->invoice->due_date->format('d/m')
            );
            
            $smsService->send($filho->phone, $message);
        }
        
        // Enviar email se tiver
        if ($filho->user?->email && !str_contains($filho->user->email, '@maosestendidas.local')) {
            // Mail::to($filho->user->email)->send(new InvoiceCreatedMail($this->invoice));
        }
        
        // Marcar notificação como enviada
        $this->invoice->update([
            'notification_sent' => true,
            'notification_sent_at' => now(),
        ]);
        
        Log::info("Notificação de fatura enviada", [
            'invoice_id' => $this->invoice->id,
            'filho_id' => $filho->id,
        ]);
    }
}
<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\ZApiApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Notifications\SendSubscriptionInvoiceWhatsapp;

class SendSubscriptionInvoiceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function handle(): void
    {
        $filho = $this->invoice->filho;
        
        if (!$filho || !$filho->phone) {
            return;
        }

        $phone = $filho->phone;

        try{

            // 2. Variabilidade de Saudação (Evita o padrão de Spam)
            $saudacoes = ['Olá', 'Oi', 'Tudo bem?', 'E aí!', 'Oi Filho!'];
            $saudacao = $saudacoes[array_rand($saudacoes)];
            $message = sprintf(
                '%s : Sua mensalidade de %s no valor de R$ %s vence em %s.',
                $saudacao,
                $this->invoice->period_start->format('M/Y'),
                number_format($this->invoice->total_amount, 2, ',', '.'),
                $this->invoice->due_date->format('d/m')
            );

            $zapi = app(ZApiApiService::class);
            // 1. Simula "Digitando..." por um tempo aleatório
            $zapi->sendPresence($phone, 'composing');
            $delaySeconds = now()->addSeconds(rand(5, 60));

            //Envia notificação
            $filho->notify( (new SendSubscriptionInvoiceWhatsapp($message) )->delay($delaySeconds) );
    
        }catch(\Exception $e){
            Log::error('Não foi possivel enviar notificação de renovação de assinatura: '.$e->getMessage());
        }
        
        
        $this->invoice->update([ 'notification_sent' => true, 'notification_sent_at' => now() ]);
        
        Log::info("Notificação de mensalidade enviada", [
            'invoice_id' => $this->invoice->id,
            'filho_id' => $filho->id,
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\Filho;
use App\Models\Invoice;
use App\Services\ZApiApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInvoiceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Filho $filho, public Invoice $invoice) {}

    public function handle(ZApiApiService $zapi)
    {
        if (!$this->filho || !$this->filho->phone) {
            return;
        }

        $saudacoes = ['Olá', 'Bom dia', 'Tudo bem?', 'Saudações'];
        $saudacao = $saudacoes[array_rand($saudacoes)];
        
        $vencimento = $this->invoice->due_date->format('d/m/Y');
        $valor = number_format($this->invoice->total_amount, 2, ',', '.');
        
        $mensagem = "{$saudacao}, {$this->filho->user->name}! 👋\n\n" .
                    "Informamos que o fechamento da fatura de consumo (Lojinha) referente ao mês passado foi realizado.\n\n" .
                    "*Resumo:* \n" .
                    "💰 Valor: R$ {$valor}\n" .
                    "📅 Vencimento: {$vencimento}\n\n" .
                    "Você pode acessar os detalhes e o boleto no seu painel. Em caso de dúvidas, estamos à disposição!";

        // Envia presença de 'digitando' por 3 segundos para parecer humano
        $zapi->sendPresence($this->filho->phone, 'composing');
        sleep(rand(5, 8));
        $zapi->sendMessage($this->filho->phone, $mensagem);

    }

}

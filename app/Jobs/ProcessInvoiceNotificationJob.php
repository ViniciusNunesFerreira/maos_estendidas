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

        $name_parts = explode(' ', $this->filho->full_name);
        $name = $name_parts[0]; 
        $vencimento = $this->invoice->due_date->format('d/m/Y');

        $saudacoes = ["Olá, {$name}! Tudo bem por aí?", "Bom dia, {$name}! como você está? ", "Oii, tudo bem {$name}?", "Oii {$name}, cê tá bem?", 'E ai, tudo bem?', "Oii tudo bom?", "Olá {$name}, como estão as coisas?", "Oi amor, tudo bem?"];
        $corpos = [
            "O fechamento da lojinha do mês passado foi realizado.",
            "O fechamento mensal com vencimento: {$vencimento}, foi realizado.",
            "Passando para informar que processamos seu fechamento da lojinha, do mês passado.",
            "Já fizemos o seu fechamento da lojinha do mês passado.",
            "Só pra avisar que o sistema já calculou o fechamento do mês, que vence em: {$vencimento}.",
        ];
       
        $fechamentos = [ 'Qualquer dúvida é só chamar. Abraço!', 
                    'Confere lá no app.', 
                    'Pode confirmar se recebeu?',
                    'Só conferir no seu aplicativo.', 
                    'Dá uma conferida e qualquer dúvida pode chamar...', 
                    'Agora você pode ver os detalhes no seu app, tá.',
                    'Dá uma olhada se recebeu e  me confirma, ok.'];


        $msg = $saudacoes[array_rand($saudacoes)] . "\n\n";
        $msg .= $corpos[array_rand($corpos)] . "\n\n";
        $msg .= $fechamentos[array_rand($fechamentos)];

        // Envia presença de 'digitando' por 5 segundos para parecer humano
        $zapi->sendPresence($this->filho->phone, 'composing');

        $typingSeconds = ceil(strlen($msg) / 12) + rand(2, 5);
        sleep($typingSeconds);

        $zapi->sendMessage($this->filho->phone, $msg);

    }

}

<?php

namespace App\Jobs;

use App\Models\Filho;
use App\Services\ZApiApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMotivationalMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Tentativas caso a API falhe
    public $tries = 3;

    public function __construct(
        protected Filho $filho,
        protected string $mensagem
    ) {}

    public function handle(ZApiApiService $zapi)
    {
        // 1. Validar se o número existe (usando sua ZApiApiService)
        if (!$zapi->checkValidity($this->filho->phone)) {
            return;
        }

        // 2. Simular Presença: "Digitando..."
        // Em 2026, a Meta monitora o tempo entre o 'composing' e o 'send'
        $zapi->sendPresence($this->filho->phone, 'composing');

        // Calculamos um tempo de digitação baseado no tamanho da frase
        // Média de 12 caracteres por segundo + um fator aleatório
        $typingSeconds = ceil(strlen($this->mensagem) / 12) + rand(2, 8);
        
        sleep($typingSeconds);

        // 3. Enviar a mensagem final
        $zapi->sendMessage($this->filho->phone, $this->mensagem);
    }
}

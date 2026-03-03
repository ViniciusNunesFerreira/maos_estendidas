<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MotivationalMessageService;

class PaiDeSantoMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pai-de-santo-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envio da mensagem aleatória do Pai de Santo';

    /**
     * Execute the console command.
     */
    public function handle(MotivationalMessageService $service)
    {
            $service->sendDailyBlessings(15); // Testa com um grupo de 5
            $this->info("Sorteio realizado e jobs enviados para a fila.");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionService;

class ProcessSubscriptionRenewals extends Command
{
    
    protected $signature = 'billing:process-renewals';

    protected $description = 'Processa as renovações de assinaturas do Mãos Estendidas e gera novas faturas';

    
    public function handle(SubscriptionService $subscriptionService)
    {
        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Iniciando processamento de assinaturas recorrentes...');
        
        $results = $subscriptionService->processAutoRenewals();
        
        $this->info("Operação finalizada. Sucesso: {$results['success']} | Falhas: {$results['failed']}");
        
        if ($results['failed'] > 0) {
            foreach ($results['errors'] as $error) {
                $this->error($error);
            }
        }

        return Command::SUCCESS;
    }
}
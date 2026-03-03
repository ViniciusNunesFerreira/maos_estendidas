<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceService;

class GenerateMonthlyInvoices extends Command
{

    protected $signature = 'app:generate-monthly-invoices';

    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $service)
    {
        $this->info('Iniciando geração de faturas mensais...');
            $service->generateMonthlyInvoices();
        $this->info('Processo finalizado e notificações enfileiradas.');
    }
}

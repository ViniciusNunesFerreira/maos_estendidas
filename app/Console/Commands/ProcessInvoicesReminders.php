<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;

class ProcessInvoicesReminders extends Command
{
    protected $signature = 'billing:send-invoice-reminders';

    protected $description = 'Relebra vencimento de faturas próximo de 3 dias';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Iniciando processamento de notificação de faturas próximas do vendimento...');

        return Command::SUCCESS;
    }
}

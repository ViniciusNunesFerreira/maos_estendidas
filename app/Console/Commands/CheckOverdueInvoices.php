<?php
// app/Console/Commands/CheckOverdueInvoices.php

namespace App\Console\Commands;

use App\Jobs\CheckOverdueInvoicesJob;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'invoices:check-overdue';
    protected $description = 'Check and mark overdue invoices';

    public function handle(): int
    {
        $this->info('Checking overdue invoices...');

        CheckOverdueInvoicesJob::dispatch();

        $this->info('Check completed successfully!');

        return Command::SUCCESS;
    }
}
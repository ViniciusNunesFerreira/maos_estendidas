<?php
// app/Console/Commands/GenerateMonthlyInvoices.php

namespace App\Console\Commands;

use App\Jobs\ProcessMonthlyInvoicesJob;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate {--month=} {--year=}';
    protected $description = 'Generate monthly invoices for all active filhos';

    public function handle(): int
    {
        $month = $this->option('month') ?? now()->subMonth()->month;
        $year = $this->option('year') ?? now()->subMonth()->year;

        $this->info("Generating invoices for {$month}/{$year}...");

        ProcessMonthlyInvoicesJob::dispatch((int)$month, (int)$year);

        $this->info('Invoice generation job dispatched successfully!');

        return Command::SUCCESS;
    }
}
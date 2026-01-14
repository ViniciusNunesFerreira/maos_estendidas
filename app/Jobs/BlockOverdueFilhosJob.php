<?php
// app/Jobs/BlockOverdueFilhosJob.php

namespace App\Jobs;

use App\Models\Filho;
use App\Models\Invoice;
use App\Notifications\Filho\AccountBlockedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BlockOverdueFilhosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $filhos = Filho::where('status', 'active')
            ->whereHas('invoices', function ($query) {
                $query->where('status', 'overdue');
            })
            ->get();

        foreach ($filhos as $filho) {
            $overdueCount = Invoice::where('filho_id', $filho->id)
                ->where('status', 'overdue')
                ->count();

            // Block if 2 or more overdue invoices
            if ($overdueCount >= 2) {
                $filho->update(['status' => 'blocked']);
                $filho->notify(new AccountBlockedNotification($overdueCount));
            }
        }
    }
}
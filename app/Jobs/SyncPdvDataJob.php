<?php
// app/Jobs/SyncPdvDataJob.php

namespace App\Jobs;

use App\Services\SyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPdvDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $pdvId,
        public readonly array $data
    ) {}

    public function handle(SyncService $syncService): void
    {
        try {
            $syncService->syncPdvData($this->pdvId, $this->data);
            
            Log::info('PDV data synced successfully', [
                'pdv_id' => $this->pdvId,
                'data_count' => count($this->data)
            ]);
        } catch (\Exception $e) {
            Log::error('PDV sync failed', [
                'pdv_id' => $this->pdvId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

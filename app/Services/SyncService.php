<?php

namespace App\Services;

use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SyncService
{
    /**
     * Registrar download de sincronização
     */
    public function registerDownload(string $deviceId, Carbon $timestamp): SyncLog
    {
        return SyncLog::create([
            'device_id' => $deviceId,
            'type' => 'download',
            'status' => 'completed',
            'synced_at' => $timestamp,
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }

    /**
     * Registrar upload de sincronização
     */
    public function registerUpload(
        string $deviceId,
        Carbon $timestamp,
        int $successCount,
        int $errorCount
    ): SyncLog {
        return SyncLog::create([
            'device_id' => $deviceId,
            'type' => 'upload',
            'status' => $errorCount > 0 ? 'partial' : 'completed',
            'synced_at' => $timestamp,
            'records_synced' => $successCount,
            'records_failed' => $errorCount,
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }

    /**
     * Obter último download do device
     */
    public function getLastDownload(string $deviceId): ?Carbon
    {
        $log = SyncLog::where('device_id', $deviceId)
            ->where('type', 'download')
            ->where('status', 'completed')
            ->orderByDesc('synced_at')
            ->first();

        return $log?->synced_at;
    }

    /**
     * Obter último upload do device
     */
    public function getLastUpload(string $deviceId): ?Carbon
    {
        $log = SyncLog::where('device_id', $deviceId)
            ->where('type', 'upload')
            ->orderByDesc('synced_at')
            ->first();

        return $log?->synced_at;
    }

    /**
     * Obter histórico de sincronização do device
     */
    public function getDeviceHistory(string $deviceId, int $limit = 10): array
    {
        return SyncLog::where('device_id', $deviceId)
            ->orderByDesc('synced_at')
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'type' => $log->type,
                'status' => $log->status,
                'synced_at' => $log->synced_at->toIso8601String(),
                'records_synced' => $log->records_synced,
                'records_failed' => $log->records_failed,
            ])
            ->toArray();
    }

    /**
     * Resetar sincronização do device
     */
    public function resetDevice(string $deviceId): void
    {
        SyncLog::where('device_id', $deviceId)->delete();
        
        Cache::forget("device_sync_{$deviceId}");
    }

    /**
     * Verificar se device precisa sincronizar
     */
    public function needsSync(string $deviceId, int $maxMinutes = 30): bool
    {
        $lastSync = $this->getLastDownload($deviceId);

        if (!$lastSync) {
            return true;
        }

        return $lastSync->diffInMinutes(now()) > $maxMinutes;
    }

    /**
     * Obter devices ativos
     */
    public function getActiveDevices(int $lastHours = 24): \Illuminate\Support\Collection
    {
        return SyncLog::query()
            ->where('synced_at', '>=', now()->subHours($lastHours))
            ->select('device_id')
            ->selectRaw('MAX(synced_at) as last_sync')
            ->selectRaw('COUNT(*) as sync_count')
            ->groupBy('device_id')
            ->orderByDesc('last_sync')
            ->get();
    }

    /**
     * Obter estatísticas de sincronização
     */
    public function getStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_syncs' => SyncLog::where('synced_at', '>=', $startDate)->count(),
            'downloads' => SyncLog::where('synced_at', '>=', $startDate)->where('type', 'download')->count(),
            'uploads' => SyncLog::where('synced_at', '>=', $startDate)->where('type', 'upload')->count(),
            'failed' => SyncLog::where('synced_at', '>=', $startDate)->where('status', 'failed')->count(),
            'active_devices' => SyncLog::where('synced_at', '>=', $startDate)->distinct('device_id')->count('device_id'),
            'records_synced' => SyncLog::where('synced_at', '>=', $startDate)->sum('records_synced'),
        ];
    }

    /**
     * Limpar logs antigos
     */
    public function cleanOldLogs(int $daysToKeep = 30): int
    {
        return SyncLog::where('synced_at', '<', now()->subDays($daysToKeep))->delete();
    }
}
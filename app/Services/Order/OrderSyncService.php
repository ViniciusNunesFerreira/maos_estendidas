<?php
// app/Services/Order/OrderSyncService.php

namespace App\Services\Order;

use App\DTOs\Order\OrderDTO;
use App\Models\Order;
use App\Models\SyncQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function syncBatch(array $orders): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'duplicates' => [],
        ];

        foreach ($orders as $orderData) {
            try {
                $result = $this->syncSingleOrder($orderData);
                
                if ($result['status'] === 'duplicate') {
                    $results['duplicates'][] = $result;
                } else {
                    $results['success'][] = $result;
                }
            } catch (\Exception $e) {
                Log::error("Erro ao sincronizar pedido", [
                    'order' => $orderData,
                    'error' => $e->getMessage(),
                ]);
                
                $results['failed'][] = [
                    'sync_uuid' => $orderData['sync_uuid'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function syncSingleOrder(array $orderData): array
    {
        return DB::transaction(function () use ($orderData) {
            // Verificar duplicação pelo sync_uuid
            if (isset($orderData['sync_uuid'])) {
                $existing = Order::where('sync_uuid', $orderData['sync_uuid'])->first();
                
                if ($existing) {
                    return [
                        'status' => 'duplicate',
                        'order_id' => $existing->id,
                        'message' => 'Pedido já sincronizado',
                    ];
                }
            }

            // Converter para DTO e criar pedido
            $dto = OrderDTO::fromArray($orderData);
            $order = $this->orderService->create($dto);

            // Marcar como sincronizado
            $order->update([
                'is_synced' => true,
                'synced_at' => now(),
            ]);

            // Remover da fila de sincronização se existir
            if (isset($orderData['sync_uuid'])) {
                SyncQueue::where('sync_uuid', $orderData['sync_uuid'])->delete();
            }

            return [
                'status' => 'success',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ];
        });
    }

    public function getPendingSync(string $deviceId): array
    {
        return SyncQueue::where('device_id', $deviceId)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'entity_type' => $item->entity_type,
                'operation' => $item->operation,
                'payload' => $item->payload,
                'created_at' => $item->created_at,
            ])
            ->toArray();
    }
}
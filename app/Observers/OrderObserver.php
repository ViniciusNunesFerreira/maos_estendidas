<?php
// app/Observers/OrderObserver.php

namespace App\Observers;

use App\Models\Order;
use App\Models\StockMovement;
use App\Services\StockService;

class OrderObserver
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function created(Order $order): void
    {
        
        /*if ($order->status === 'pending') {

            try {
                \Log::info('reservando stoque');
                $this->stockService->reserveStock($order);
            } catch (\Exception $e) {
                \Log::error("Falha ao reservar estoque para Pedido {$order->id}: " . $e->getMessage());
                // Em um sistema premium, você poderia cancelar o pedido aqui se o estoque falhar
            }
        }*/
    }

   public function updated(Order $order): void
    {
        
        // Se o status não mudou, não faz nada
        if (!$order->isDirty('status')) {
            return;
        }

        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        // DEFINIÇÃO DE ESTADOS (Nível Sênior)
        $isConfirming = ($oldStatus === 'pending' && in_array($newStatus, [ 'ready', 'paid', 'delivered', 'completed']));
        $isCancelling = ($newStatus === 'cancelled');

        
        if ($isConfirming) {
            // Verifica se ainda existe uma reserva para este pedido antes de dar a baixa
            $hasReservation = StockMovement::where('order_id', $order->id)
                ->where('type', 'reserve')
                ->exists();

            if ($hasReservation) {
                $this->stockService->confirmStockExit($order);
            }
        }

        // Cenário 2: Pedido Cancelado
        if ($isCancelling) {
            if ($oldStatus === 'pending') {
                // Se era pendente, apenas removemos a reserva "fantasma"
                $this->stockService->rollbackReservation($order);
            }else {
                // Se já estava em 'ready' ou 'preparing', o estoque físico já foi baixado.
                $this->stockService->returnToPhysicalStock($order);
            } 
        }


    }
}
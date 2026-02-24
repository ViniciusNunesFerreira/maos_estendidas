<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class ExpireOldReservations extends Command
{

    protected $signature = 'orders:expire-reservations';
    protected $description = 'Cancela reservas de pedidos pendentes há mais de 15 minutos';



    /**
     * Execute the console command.
     */
    public function handle()
    {
        
        $minutes = 15;
    
        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->get();

        if ($expiredOrders->isEmpty()) {
            return;
        }

        foreach ($expiredOrders as $order) {
            // O Observer vai detectar o status 'cancelled' e chamar o rollbackStock automaticamente
            $order->update([
                'status' => 'cancelled', 
                'cancellation_reason' => "Reserva expirada automaticamente após {$minutes} min"
            ]);
            
            \Log::info("Pedido #{$order->order_number} cancelado por expiração de tempo.");
        }

        $this->info(count($expiredOrders) . " pedidos expirados foram limpos.");


    }
}

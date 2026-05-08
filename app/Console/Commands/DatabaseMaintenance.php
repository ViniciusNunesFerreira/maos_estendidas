<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseMaintenance extends Command
{
    /**
     * O nome e assinatura do console.
     * Define retenção padrão de 10 dias para o lixo.
     */
    protected $signature = 'db:maintenance {--days=10 : Dias de retenção de dados falhos/cancelados}';

    protected $description = 'Limpa registros de lixo gerados por intents e orders canceladas ou pendentes sem conclusão.';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Iniciando limpeza de banco de dados (Retenção: {$days} dias)...");
        Log::info("Iniciando rotina db:maintenance - Corte: {$cutoffDate}");

        // 1. Limpar Payment Intents abandonados/rejeitados/com erro e sem vínculos maiores
        $intentsDeleted = 0;
        PaymentIntent::whereIn('status', ['cancelled', 'error', 'rejected'])
            ->where('created_at', '<', $cutoffDate)
            ->chunkById(100, function ($intents) use (&$intentsDeleted) {
                foreach ($intents as $intent) {
                    $intent->forceDelete();
                    $intentsDeleted++;
                }
            });

        // 2. Limpar Orders e seus itens pendentes antigos e cancelados
        // Regra: Status 'cancelled' ou ('pending' E muito antigos que foram abandonados)
        $ordersDeleted = 0;
        Order::where('created_at', '<', $cutoffDate)
            ->where(function ($query) {
                $query->where('status', 'cancelled')
                      ->orWhere('status', 'pending');
            })
            ->chunkById(100, function ($orders) use (&$ordersDeleted) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$ordersDeleted) {
                        
                        $orderId = $order->id;

                        // 2.1 Limpar itens do pedido
                        $order->items()->forceDelete();

                        // 2.2 Limpar Payment Intents da ordem
                        PaymentIntent::where('order_id', $orderId)->forceDelete();

                       
                        Payment::where('order_id', $orderId)->forceDelete();

                        // 2.4 CORREÇÃO: Limpar movimentações de caixa vinculadas à ordem
                        DB::table('cash_movements')->where('order_id', $orderId)->delete();

                        // 2.5 Por fim, forçar a exclusão da Ordem
                        $order->forceDelete();
                        
                        $ordersDeleted++;
                    });
                }
            });

        $this->info("Concluído. PaymentIntents deletados: {$intentsDeleted} | Orders deletadas: {$ordersDeleted}");
        Log::info("Rotina db:maintenance concluída.", ['intents_cleared' => $intentsDeleted, 'orders_cleared' => $ordersDeleted]);
    }
}
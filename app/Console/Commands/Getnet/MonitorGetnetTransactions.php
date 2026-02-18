<?php

namespace App\Console\Commands\Getnet;

use App\Models\GetnetTransaction;
use App\Services\Payment\Getnet\GetnetCloudService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Monitora transações Getnet pendentes e aplica timeout
 * 
 * Deve ser executado via Scheduler a cada minuto
 */
class MonitorGetnetTransactions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'getnet:monitor-transactions
                            {--timeout=5 : Timeout em minutos}
                            {--force : Força verificação via API}';

    /**
     * The console command description.
     */
    protected $description = 'Monitora transações Getnet pendentes e aplica timeout quando necessário';

    /**
     * Execute the console command.
     */
    public function handle(GetnetCloudService $getnetService): int
    {
        $timeout = (int) $this->option('timeout');
        $force = $this->option('force');
        
        $this->info("Iniciando monitoramento de transações Getnet (timeout: {$timeout} min)...");

        // Busca transações pendentes
        $transactions = GetnetTransaction::pending()
            ->where('created_at', '<', now()->subMinutes($timeout))
            ->with(['order', 'paymentIntent'])
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('Nenhuma transação pendente com timeout encontrada.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$transactions->count()} transações pendentes.");

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        $timeoutCount = 0;
        $checkedCount = 0;
        $errorCount = 0;

        foreach ($transactions as $transaction) {
            try {
                // Se force, consulta API para verificar status
                if ($force && $transaction->payment_id) {
                    $this->line("\nConsultando status da transação {$transaction->id}...");
                    $getnetService->checkPaymentStatus($transaction);
                    $transaction = $transaction->fresh();
                    $checkedCount++;
                }

                // Se ainda está pendente após consulta, aplica timeout
                if ($transaction->is_pending) {
                    $this->line("\nAplicando timeout na transação {$transaction->id}...");
                    
                    $transaction->markAsTimeout();
                    $transaction->paymentIntent->markAsRejected('Tempo limite excedido');
                    
                    // Remove flag do pedido
                    $transaction->order->update([
                        'awaiting_external_payment' => false,
                    ]);
                    
                    $timeoutCount++;
                    
                    Log::warning('Getnet Monitor: Timeout aplicado', [
                        'transaction_id' => $transaction->id,
                        'order_id' => $transaction->order_id,
                        'elapsed_time' => $transaction->elapsed_time,
                    ]);
                }

            } catch (\Exception $e) {
                $this->error("\nErro ao processar transação {$transaction->id}: {$e->getMessage()}");
                $errorCount++;
                
                Log::error('Getnet Monitor: Erro ao processar transação', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumo
        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['Total Processadas', $transactions->count()],
                ['Consultadas na API', $checkedCount],
                ['Timeout Aplicado', $timeoutCount],
                ['Erros', $errorCount],
            ]
        );

        if ($errorCount > 0) {
            $this->warn("Monitoramento concluído com {$errorCount} erro(s). Verifique os logs.");
            return self::FAILURE;
        }

        $this->info('Monitoramento concluído com sucesso!');
        return self::SUCCESS;
    }
}
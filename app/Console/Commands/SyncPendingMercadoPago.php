<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentIntent;
use App\Models\PaymentSetting;
use App\Services\ExternalPaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPendingMercadoPago extends Command
{
    /**
     * O nome e assinatura do comando.
     */
    protected $signature = 'mp:sync-pending {--days=3}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Sincroniza pagamentos pendentes consultando a API do Mercado Pago diretamente.';

    public function handle(ExternalPaymentService $paymentService)
    {
        $days = (int) $this->option('days');
        
        $this->info("Iniciando conciliação de pagamentos dos últimos {$days} dias...");

        // 1. Usa o escopo nativo 'pending()' criado no seu PaymentIntent.php
        $pendingIntents = PaymentIntent::pending()
            ->whereNotNull('mp_payment_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($pendingIntents->isEmpty()) {
            $this->info('Nenhum pagamento pendente (com ID do MP) encontrado nesse período.');
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$pendingIntents->count()} pagamentos pendentes. Consultando API do Mercado Pago...");

        $bar = $this->output->createProgressBar($pendingIntents->count());
        $bar->start();

        // 2. Resgata o token de forma segura baseada no seu PaymentSetting.php
        $token = PaymentSetting::getMercadoPagoConfig()?->access_token 
                 ?? config('services.mercadopago.access_token');

        if (empty($token)) {
            $this->error("\nFalha: Token de acesso do Mercado Pago não encontrado.");
            return Command::FAILURE;
        }

        $pagamentosBaixados = 0;

        foreach ($pendingIntents as $intent) {
            try {
                // 3. Consulta direto na fonte da verdade (Mercado Pago)
                $response = Http::withToken($token)
                    ->get("https://api.mercadopago.com/v1/payments/{$intent->mp_payment_id}");

                if ($response->successful()) {
                    $mpData = $response->json();
                    $mpStatus = $mpData['status'] ?? null;

                    if ($mpStatus === 'approved') {
                        // CORREÇÃO ESTRUTURAL CRÍTICA:
                        // Passa a instância real do $intent e o payload $mpData 
                        // Exatamente como exigido pelo ExternalPaymentService->processPaymentConfirmation
                        $paymentService->processPaymentConfirmation($intent, $mpData);

                        $pagamentosBaixados++;
                        Log::info("[Conciliação MP] Pagamento {$intent->mp_payment_id} (Intent ID: {$intent->id}) recuperado e baixado com sucesso.");
                    } 
                    elseif ($mpStatus === 'cancelled') {
                        // Usa o método nativo do seu model
                        $intent->markAsCancelled('Cancelado no Mercado Pago (Conciliação)');
                        Log::info("[Conciliação MP] Intent {$intent->id} atualizado para Cancelado.");
                    }
                    elseif ($mpStatus === 'rejected') {
                        // Usa o método nativo do seu model
                        $intent->markAsRejected('Rejeitado no Mercado Pago (Conciliação)');
                        Log::info("[Conciliação MP] Intent {$intent->id} atualizado para Rejeitado.");
                    }
                }
            } catch (\Exception $e) {
                Log::error("[Conciliação MP] Erro ao sincronizar Intent ID {$intent->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine(2);
        $this->info("Conciliação finalizada! {$pagamentosBaixados} pagamentos aprovados foram recuperados e faturados no sistema.");

        return Command::SUCCESS;
    }
}
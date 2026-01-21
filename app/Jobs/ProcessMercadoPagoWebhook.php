<?php

namespace App\Jobs;

use App\Models\PaymentWebhook;
use App\Http\Controllers\Api\V1\Webhook\MercadoPagoWebhookController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processar webhooks do Mercado Pago de forma assíncrona
 */
class ProcessMercadoPagoWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas
     */
    public $tries = 3;

    /**
     * Timeout em segundos
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PaymentWebhook $webhook
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MercadoPagoWebhookController $controller): void
    {
        Log::info('Job - Processando webhook MP', [
            'webhook_id' => $this->webhook->id,
            'attempt' => $this->attempts(),
        ]);

        try {
            $controller->process($this->webhook);

        } catch (\Exception $e) {
            Log::error('Job - Erro ao processar webhook', [
                'webhook_id' => $this->webhook->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Se falhou 3 vezes, marcar como failed
            if ($this->attempts() >= $this->tries) {
                $this->webhook->markAsFailed('Falhou após 3 tentativas: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job - Webhook falhou definitivamente', [
            'webhook_id' => $this->webhook->id,
            'error' => $exception->getMessage(),
        ]);

        $this->webhook->markAsFailed($exception->getMessage());
    }
}
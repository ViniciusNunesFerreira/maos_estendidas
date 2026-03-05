<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $subscriptionId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SubscriptionService $subscriptionService): void
    {
        $subscription = Subscription::find($this->subscriptionId);

        if (!$subscription) {
            Log::error('Subscription not found for renewal', [
                'subscription_id' => $this->subscriptionId
            ]);
            return;
        }

        // Verificar se ainda está ativa
        if ($subscription->status !== 'active') {
            Log::info('Subscription no longer active, skipping renewal', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status
            ]);
            return;
        }

        try {
            // Processar renovação
            $subscriptionService->processRenewal($subscription);

            Log::info('Subscription renewal processed successfully', [
                'subscription_id' => $subscription->id,
                'next_billing' => $subscription->next_billing_date
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process subscription renewal', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSubscriptionRenewalJob failed after all retries', [
            'subscription_id' => $this->subscriptionId,
            'exception' => $exception->getMessage(),
        ]);

        // Opcional: Notificar administradores sobre falha na renovação
    }
}
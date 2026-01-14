<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Jobs\ProcessSubscriptionRenewalJob;
use Illuminate\Support\Facades\Log;

class SubscriptionObserver
{
    /**
     * Handle the Subscription "created" event.
     */
    public function created(Subscription $subscription): void
    {
        Log::info('Subscription created', [
            'subscription_id' => $subscription->id,
            'filho_id' => $subscription->filho_id,
            'amount' => $subscription->amount,
        ]);

        // Se a assinatura já começou, agendar próxima cobrança
        if ($subscription->status === 'active' && $subscription->next_billing_date) {
            $this->scheduleNextBilling($subscription);
        }
    }

    /**
     * Handle the Subscription "updated" event.
     */
    public function updated(Subscription $subscription): void
    {
        // Se o status mudou para active
        if ($subscription->isDirty('status') && $subscription->status === 'active') {
            Log::info('Subscription activated', [
                'subscription_id' => $subscription->id,
                'next_billing' => $subscription->next_billing_date,
            ]);

            $this->scheduleNextBilling($subscription);
        }

        // Se o status mudou para cancelled
        if ($subscription->isDirty('status') && $subscription->status === 'cancelled') {
            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'cancelled_at' => $subscription->cancelled_at,
            ]);

            // Cancelar jobs agendados
            // Nota: Implementar cancelamento de jobs se necessário
        }

        // Se a data de próxima cobrança mudou
        if ($subscription->isDirty('next_billing_date')) {
            $this->scheduleNextBilling($subscription);
        }
    }

    /**
     * Handle the Subscription "deleted" event.
     */
    public function deleted(Subscription $subscription): void
    {
        Log::warning('Subscription deleted', [
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Agendar próxima cobrança da assinatura
     */
    private function scheduleNextBilling(Subscription $subscription): void
    {
        if ($subscription->status !== 'active' || !$subscription->next_billing_date) {
            return;
        }

        // Dispatch job para processar na data de cobrança
        ProcessSubscriptionRenewalJob::dispatch($subscription->id)
            ->delay($subscription->next_billing_date);

        Log::info('Subscription renewal scheduled', [
            'subscription_id' => $subscription->id,
            'scheduled_for' => $subscription->next_billing_date,
        ]);
    }
}
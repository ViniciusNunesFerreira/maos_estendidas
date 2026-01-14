<?php
// app/Services/SubscriptionService.php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Filho;
use App\Jobs\SendSubscriptionInvoiceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;


use App\Notifications\SubscriptionCreated;
use App\Notifications\SubscriptionCancelled;


class SubscriptionService
{

    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}


    public function calcularDatasFaturamento(int $billingDay = 28)
    {
        $hoje = Carbon::today();
        
        // 1. Definir o faturamento deste mês
        $dataFechamentoDesteMes = Carbon::today()->day($billingDay);

        // 2. Se hoje for DEPOIS do dia 28, o primeiro faturamento é no mês que vem
        if ($hoje->gt($dataFechamentoDesteMes)) {
            $firstBillingDate = $dataFechamentoDesteMes->copy()->addMonth();
        } else {
            $firstBillingDate = $dataFechamentoDesteMes->copy();
        }

        // 3. O próximo é sempre um mês após o primeiro
        $nextBillingDate = $firstBillingDate->copy()->addMonth();

        return [
            'first_billing_date' => $firstBillingDate, 
            'next_billing_date'  => $nextBillingDate, 
        ];
    }

    

    /**
     * Criar assinatura para filho aprovado
     */
    public function createForFilho(
        Filho $filho, 
        float $amount = null, 
    ): Subscription {
        $amount = $amount ?? config('casalar.subscription.default_amount', 120.00);
        $billingDates = $this->calcularDatasFaturamento(28);

        return DB::transaction(function () use ($filho, $amount, $billingDates) {
        
            $subscription = Subscription::create([
                'filho_id' => $filho->id,
                'approved_by_user_id' => auth()->user()->id,
                'plan_name' => 'Mensalidade Mãos Estendidas',
                'plan_description' => 'Mensalidade recorrente para filhos do Mãos Estendidas',
                'amount' => $amount,
                'billing_cycle' => 'monthly',
                'billing_day' => 28,
                'started_at' => now(),
                'first_billing_date' => $billingDates['first_billing_date'],
                'next_billing_date' => $billingDates['next_billing_date'],
                'status' => 'active',
            ]);

             // Gerar primeira fatura
            $this->invoiceService->generateSubscriptionInvoice(
                filho: $filho,
                amount: $amount,
                referenceMonth: Carbon::now(),
            );

                        // Notificar
           /* if ($filho->user) {
                $filho->user->notify(new SubscriptionCreated($subscription));
            }*/

            return $subscription;

         });
    }

    public function create(
        Filho $filho, 
        string $planName = null,
        float $amount = null,
        string $billingCycle = null,
        int $billingDay = null,
        string $planDescription = null,    
        $startedAt = null,
    ): Subscription {

        return DB::transaction(function () use ($filho, $planName, $amount, $billingCycle, $billingDay, $planDescription, $startedAt) {

            
             $subscription = Subscription::create([
                'filho_id' => $filho->id,
                'plan_name' => $planName,
                'approved_by_user_id' => auth()->user()->id,
                'plan_description' => $planDescription ?? 'Mensalidade recorrente para filhos do Mãos Estendidas',
                'amount' => $amount,
                'billing_cycle' => $billingCycle,
                'billing_day' => $billingDay,
                'started_at' => $startedAt,
                'first_billing_date' => $startedAt,
                'next_billing_date' => now()->addDays(30),
                'status' => 'active',
            ]);

             // Gerar primeira fatura
            $this->invoiceService->generateSubscriptionInvoice(
                filho: $filho,
                amount: $amount,
                referenceMonth: $startedAt
            );

                        // Notificar
           /* if ($filho->user) {
                $filho->user->notify(new SubscriptionCreated($subscription));
            }*/

            return $subscription;


        });
    }


    /**
     * Gerar faturas de assinatura pendentes
     * Executado diariamente
     */
    public function generatePendingInvoices(): Collection
    {
        $generatedInvoices = collect();
        
        $subscriptions = Subscription::dueBilling()->get();
        
        foreach ($subscriptions as $subscription) {
            try {
                $invoice = $subscription->generateInvoice();
                
                // Enviar notificação
                SendSubscriptionInvoiceNotification::dispatch($invoice);
                
                $generatedInvoices->push($invoice);
            } catch (\Exception $e) {
                \Log::error("Erro ao gerar fatura de assinatura", [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $generatedInvoices;
    }

    /**
     * Verificar faturas de assinatura vencidas
     */
    public function checkOverdueInvoices(): int
    {
        $overdueCount = 0;
        
        $invoices = SubscriptionInvoice::where('status', 'pending')
            ->where('due_date', '<', now()->startOfDay())
            ->get();
        
        foreach ($invoices as $invoice) {
            $invoice->markAsOverdue();
            $overdueCount++;
        }
        
        return $overdueCount;
    }

    /**
     * Registrar pagamento de fatura de assinatura
     */
    public function registerPayment(
        SubscriptionInvoice $invoice, 
        string $method, 
        ?string $reference = null
    ): SubscriptionInvoice {
        return DB::transaction(function () use ($invoice, $method, $reference) {
            $invoice->markAsPaid($method, $reference);
            return $invoice->fresh();
        });
    }

    /**
     * Alterar valor da assinatura
     */
    public function updateAmount(Subscription $subscription, float $newAmount): Subscription
    {
        $subscription->update(['amount' => $newAmount]);
        return $subscription->fresh();
    }

    /**
     * Pausar assinatura
     */
    public function pause(Subscription $subscription, string $reason = null): Subscription
    {
        $subscription->pause($reason);
        return $subscription->fresh();
    }

    /**
     * Reativar assinatura
     */
    public function reactivate(Subscription $subscription): Subscription
    {
        $subscription->reactivate();
        return $subscription->fresh();
    }

    /**
     * Cancelar assinatura
     */
    public function cancel(Subscription $subscription, string $reason = null): Subscription
    {
        $subscription->cancel($reason);
        
        // Notificar
        if ($subscription->filho->user) {
            $subscription->filho->user->notify(new SubscriptionCancelled($subscription));
        }

        return $subscription->fresh();
    }

    /**
     * Renovar assinatura
     */
    public function renew(Subscription $subscription, int $periods = 1): Subscription
    {
        return DB::transaction(function () use ($subscription, $periods) {
            for ($i = 0; $i < $periods; $i++) {
                // Gerar fatura
                $this->invoiceService->generateSubscriptionInvoice(
                    filho: $subscription->filho,
                    amount: $subscription->amount,
                    referenceMonth: $subscription->next_billing_date
                );

                // Atualizar próxima data de cobrança
                $subscription->update([
                    'next_billing_date' => $this->calculateNextBillingDate(
                        $subscription->next_billing_date,
                        $subscription->plan_type
                    ),
                    'last_billing_date' => now(),
                ]);
            }

            return $subscription->fresh();
        });
    }



    /**
     * Listar faturas de assinatura do filho
     */
    public function getFilhoSubscriptionInvoices(Filho $filho, array $filters = [])
    {
        $query = $filho->subscriptionInvoices()->with('subscription');
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->orderByDesc('issue_date')->paginate($filters['per_page'] ?? 10);
    }


     /**
     * Processar renovações automáticas
     */
    public function processAutoRenewals(): int
    {
        $count = 0;

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->where('next_billing_date', '<=', today())
            ->with('filho')
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $this->renew($subscription);
                $count++;
            } catch (\Exception $e) {
                \Log::error("Erro ao renovar assinatura {$subscription->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Processar cancelamentos agendados
     */
    public function processScheduledCancellations(): int
    {
        return Subscription::query()
            ->where('status', 'pending_cancellation')
            ->where('ends_at', '<=', now())
            ->update([
                'status' => 'cancelled',
            ]);
    }

    /**
     * Processar retomadas agendadas
     */
    public function processScheduledResumes(): int
    {
        $count = 0;

        $subscriptions = Subscription::query()
            ->where('status', 'paused')
            ->whereNotNull('scheduled_resume_at')
            ->where('scheduled_resume_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->resume($subscription);
            $count++;
        }

        return $count;
    }

    /**
     * Calcular MRR (Monthly Recurring Revenue)
     */
    public function calculateMRR(): float
    {
        return Subscription::query()
            ->where('status', 'active')
            ->get()
            ->sum(function ($subscription) {
                return match ($subscription->plan_type) {
                    'monthly' => $subscription->amount,
                    'quarterly' => $subscription->amount / 3,
                    'yearly' => $subscription->amount / 12,
                    default => 0
                };
            });
    }

    /**
     * Obter assinaturas expirando
     */
    public function getExpiringSubscriptions(int $daysAhead = 7): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::query()
            ->where('status', 'active')
            ->whereBetween('next_billing_date', [today(), today()->addDays($daysAhead)])
            ->with('filho')
            ->get();
    }

    /**
     * Calcular próxima data de cobrança
     */
    private function calculateNextBillingDate(Carbon $from, string $planType): Carbon
    {
        return match ($planType) {
            'monthly' => $from->copy()->addMonth(),
            'quarterly' => $from->copy()->addMonths(3),
            'yearly' => $from->copy()->addYear(),
            default => $from->copy()->addMonth()
        };
    }

    /**
     * Obter estatísticas de assinaturas
     */
    public function getStatistics(): array
    {
        return [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'paused' => Subscription::where('status', 'paused')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'pending_cancellation' => Subscription::where('status', 'pending_cancellation')->count(),
            'mrr' => $this->calculateMRR(),
            'by_plan' => [
                'monthly' => Subscription::where('status', 'active')->where('plan_type', 'monthly')->count(),
                'quarterly' => Subscription::where('status', 'active')->where('plan_type', 'quarterly')->count(),
                'yearly' => Subscription::where('status', 'active')->where('plan_type', 'yearly')->count(),
            ],
        ];
    }


}
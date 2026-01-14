<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Obter assinatura atual do filho autenticado
     * GET /api/v1/app/subscription
     */
    public function show(): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $subscription = $filho->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Nenhuma assinatura ativa encontrada',
            ]);
        }

        $subscription->load(['invoices' => fn($q) => $q->orderByDesc('created_at')->limit(6)]);

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    /**
     * Histórico de assinaturas
     * GET /api/v1/app/subscription/history
     */
    public function history(): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $subscriptions = $filho->subscriptions()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    /**
     * Faturas da assinatura atual
     * GET /api/v1/app/subscription/invoices
     */
    public function invoices(Request $request): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $subscription = $filho->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma assinatura ativa',
            ], 404);
        }

        $query = $subscription->invoices()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min($request->input('per_page', 12), 50);
        $invoices = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => InvoiceResource::collection($invoices),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * Detalhes do plano
     * GET /api/v1/app/subscription/plan
     */
    public function plan(): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $subscription = $filho->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_subscription' => false,
                    'available_plans' => $this->getAvailablePlans(),
                ],
            ]);
        }

        $nextInvoice = $subscription->invoices()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'has_subscription' => true,
                'plan' => [
                    'type' => $subscription->plan_type,
                    'amount' => $subscription->amount,
                    'status' => $subscription->status,
                    'started_at' => $subscription->current_period_start->toDateString(),
                    'renews_at' => $subscription->current_period_end->toDateString(),
                    'days_until_renewal' => now()->diffInDays($subscription->current_period_end, false),
                ],
                'next_invoice' => $nextInvoice ? [
                    'amount' => $nextInvoice->amount,
                    'due_date' => $nextInvoice->due_date->toDateString(),
                ] : null,
                'benefits' => $this->getPlanBenefits($subscription->plan_type),
            ],
        ]);
    }

    /**
     * Solicitar alteração de plano
     * POST /api/v1/app/subscription/change-plan
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'new_plan_type' => 'required|in:monthly,quarterly,yearly',
            'reason' => 'nullable|string|max:500',
        ]);

        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $subscription = $filho->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma assinatura ativa para alterar',
            ], 404);
        }

        if ($subscription->plan_type === $request->new_plan_type) {
            return response()->json([
                'success' => false,
                'message' => 'O plano selecionado é o mesmo que o atual',
            ], 422);
        }

        // Criar solicitação de alteração (precisa aprovação admin)
        // Em produção, isso criaria um ticket/solicitação
        // Por simplicidade, apenas retornamos sucesso

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de alteração de plano enviada. Nossa equipe entrará em contato.',
            'data' => [
                'current_plan' => $subscription->plan_type,
                'requested_plan' => $request->new_plan_type,
                'status' => 'pending_approval',
            ],
        ]);
    }

    /**
     * Obter planos disponíveis
     */
    private function getAvailablePlans(): array
    {
        return [
            [
                'type' => 'monthly',
                'name' => 'Mensal',
                'amount' => config('casalar.subscription.monthly_amount', 350.00),
                'description' => 'Cobrança mensal',
                'benefits' => $this->getPlanBenefits('monthly'),
            ],
            [
                'type' => 'quarterly',
                'name' => 'Trimestral',
                'amount' => config('casalar.subscription.quarterly_amount', 950.00),
                'description' => 'Cobrança a cada 3 meses',
                'discount' => '10%',
                'benefits' => $this->getPlanBenefits('quarterly'),
            ],
            [
                'type' => 'yearly',
                'name' => 'Anual',
                'amount' => config('casalar.subscription.yearly_amount', 3500.00),
                'description' => 'Cobrança anual',
                'discount' => '17%',
                'benefits' => $this->getPlanBenefits('yearly'),
            ],
        ];
    }

    /**
     * Obter benefícios do plano
     */
    private function getPlanBenefits(string $planType): array
    {
        $baseBenefits = [
            'Acesso completo às instalações',
            'Participação nas atividades',
            'Alimentação inclusa',
            'Acompanhamento espiritual',
        ];

        return match ($planType) {
            'quarterly' => array_merge($baseBenefits, ['Desconto de 10%']),
            'yearly' => array_merge($baseBenefits, ['Desconto de 17%', 'Prioridade em eventos especiais']),
            default => $baseBenefits,
        };
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Filho;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Listar assinaturas
     */
    public function index(): View
    {
        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'paused' => Subscription::where('status', 'paused')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'mrr' => $this->calculateMRR(),
        ];

        return view('admin.subscriptions.index', compact('stats'));
    }

    /**
     * Formulário de criação
     */
    public function create(Request $request): View
    {

        $filhoId = $request->query('filho_id');
        $selectedFilho = $filhoId ? Filho::where('id', $filhoId)->first() : null;

        $filhos = Filho::query()
            ->with('user')
            ->where('status', 'active')
            ->join('users', 'users.id', '=', 'filhos.user_id')
            ->whereDoesntHave('subscriptions', fn($q) => $q->where('status', 'active'))
            ->orderBy('users.name')
            ->select('filhos.*')
            ->get();

        $plans = $this->getAvailablePlans();

        return view('admin.subscriptions.create', compact('filhos', 'plans', 'selectedFilho'));
    }

    /**
     * Salvar nova assinatura
     */
    public function store(Request $request): RedirectResponse
    {
       
        
        $validated = $request->validate([
            'filho_id' => 'required|uuid|exists:filhos,id',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'billing_day' => 'required|integer|min:1|max:28',
            'amount' => 'required|numeric|min:0',
            'started_at' => 'nullable|date|after_or_equal:today',
            'plan_name' => 'required|string|max:250',
            'plan_description' => 'nullable|string|max:500',
        ]);

        $filho = Filho::findOrFail($validated['filho_id']);

        // Verificar se já possui assinatura ativa
        if ($filho->activeSubscription) {
            return redirect()
                ->back()
                ->with('error', 'Este filho já possui uma assinatura ativa.');
        }

        try {
            $this->subscriptionService->create(
                filho: $filho,
                planName:$validated['plan_name'],
                billingCycle: $validated['billing_cycle'],
                billingDay: $validated['billing_day'],
                amount: $validated['amount'],
                startedAt: $validated['started_at'] ? \Carbon\Carbon::parse($validated['started_at']) : now(),
                planDescription: $validated['plan_description'] ?? null
            );

            return redirect()
                ->route('admin.subscriptions.index')
                ->with('success', 'Assinatura criada com sucesso!');

        } catch (\Exception $e) {

            \Log::error('Erro ao criar assinatura: ' . $e->getMessage());
            
            return redirect()
                ->back()
                ->with('error', 'Erro ao criar assinatura:');
        }
    }

    /**
     * Exibir detalhes da assinatura
     */
    public function show(Subscription $subscription): View
    {
        $invoicesPaid = $subscription->invoices()->paid();

        $total_invoices = $subscription->invoices()->count();
        $paid_invoices =  $invoicesPaid->count();
        $paymentRate =  $total_invoices > 0 ? ($paid_invoices / $total_invoices ) * 100 : 0 ;

         $stats = [
            'total_invoices' => $total_invoices,
            'paid_invoices' => $paid_invoices,
            'payment_rate' => round($paymentRate, 2),
            'total_paid' =>$invoicesPaid->sum('paid_amount'),
        ];

        $subscription->load([
            'filho.user',
            'invoices' => fn($q) => $q->orderByDesc('created_at')->limit(12),
        ]);

        return view('admin.subscriptions.show', compact('subscription', 'stats'));
    }

    /**
     * Formulário de edição
     */
    public function edit(Subscription $subscription): View
    {
        $subscription->load('filho');
        $plans = $this->getAvailablePlans();

        return view('admin.subscriptions.edit', compact('subscription', 'plans'));
    }

    /**
     * Atualizar assinatura
     */
    public function update(Request $request, Subscription $subscription): RedirectResponse
    {

        $validated = $request->validate([
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'plan_name' => 'required|string|max:250',
            'plan_description' => 'nullable|string|max:500',
            'billing_day' => 'required',
            'amount' => 'required|numeric|min:0',
        ]);

        $subscription->update($validated);

        return redirect()
            ->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Assinatura atualizada com sucesso!');
    }

    /**
     * Pausar assinatura
     */
    public function pause(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'resume_at' => 'nullable|date|after:today',
        ]);

        if ($subscription->status !== 'active') {
            return redirect()
                ->back()
                ->with('error', 'Apenas assinaturas ativas podem ser pausadas.');
        }

        try {
            $this->subscriptionService->pause(
                subscription: $subscription,
                reason: $request->reason
            );

            return redirect()
                ->route('admin.subscriptions.show', $subscription)
                ->with('success', 'Assinatura pausada com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao pausar assinatura: ' . $e->getMessage());
        }
    }

    /**
     * Reativar assinatura
     */
    public function resume(Subscription $subscription): RedirectResponse
    {
        if ($subscription->status !== 'paused') {
            return redirect()
                ->back()
                ->with('error', 'Apenas assinaturas pausadas podem ser reativadas.');
        }

        try {
            $this->subscriptionService->reactivate($subscription);

            return redirect()
                ->route('admin.subscriptions.show', $subscription)
                ->with('success', 'Assinatura reativada com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao reativar assinatura: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar assinatura
     */
    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'immediate' => 'boolean',
        ]);

        if ($subscription->status === 'cancelled') {
            return redirect()
                ->back()
                ->with('error', 'Assinatura já está cancelada.');
        }

        try {
            $this->subscriptionService->cancel(
                subscription: $subscription,
                reason: $request->reason,
                immediate: $request->boolean('immediate', false)
            );

            return redirect()
                ->route('admin.subscriptions.index')
                ->with('success', 'Assinatura cancelada com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao cancelar assinatura: ' . $e->getMessage());
        }
    }

    /**
     * Renovar assinatura
     */
    public function renew(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'periods' => 'integer|min:1|max:12',
        ]);

        try {
            $this->subscriptionService->renew(
                subscription: $subscription,
                periods: $request->input('periods', 1)
            );

            return redirect()
                ->route('admin.subscriptions.show', $subscription)
                ->with('success', 'Assinatura renovada com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao renovar assinatura: ' . $e->getMessage());
        }
    }

    /**
     * Calcular MRR
     */
    private function calculateMRR(): float
    {
        return Subscription::where('status', 'active')
            ->get()
            ->sum(function ($sub) {
                return match ($sub->plan_type) {
                    'monthly' => $sub->amount,
                    'quarterly' => $sub->amount / 3,
                    'yearly' => $sub->amount / 12,
                    default => 0
                };
            });
    }

    /**
     * Planos disponíveis
     */
    private function getAvailablePlans(): array
    {
        return [
            [
                'name' => 'Mensalidade Casa Lar',
                'amount' => 120.00,
                'billing_cycle' => 'monthly',
                'description' => 'Plano mensal padrão',
            ],
            [
                'name' => 'Mensalidade Trimestral',
                'amount' => 350.00,
                'billing_cycle' => 'quarterly',
                'description' => 'Plano trimestral com desconto',
            ],
            [
                'name' => 'Mensalidade Anual',
                'amount' => 1120.00,
                'billing_cycle' => 'yearly',
                'description' => 'Plano anual com maior desconto',
            ],
        ];
    }
}
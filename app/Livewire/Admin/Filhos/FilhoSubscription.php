<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class FilhoSubscription extends Component
{
    // Propriedade pública - Filho é injetado automaticamente
    public Filho $filho;
    public ?Subscription $subscription = null;
    
    // Estados da interface
    public string $viewMode = 'list'; // list, create, edit
    
    // Dados do formulário
    public array $formData = [
        'plan_name' => 'Mensalidade Casa Lar',
        'amount' => 350.00,
        'billing_cycle' => 'monthly',
        'billing_day' => 10,
        'start_date' => null,
    ];
    
    // Filtros e confirmações
    public string $invoiceFilter = 'all';
    public bool $showPauseConfirm = false;
    public bool $showCancelConfirm = false;
    public bool $showResumeConfirm = false;
    public string $pauseReason = '';
    public string $cancellationReason = '';
    
    /**
     * Regras de validação
     */
    protected function rules(): array
    {
        return [
            'cancellationReason' => 'required|string|min:10|max:500',
            'pauseReason' => 'nullable|string|max:500',
            'formData.plan_name' => 'required|string|max:100',
            'formData.amount' => 'required|numeric|min:0|max:99999.99',
            'formData.billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'formData.billing_day' => 'required|integer|min:1|max:28',
            'formData.start_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    /**
     * Mensagens de validação
     */
    protected function messages(): array
    {
        return [
            'cancellationReason.required' => 'Informe o motivo do cancelamento',
            'cancellationReason.min' => 'O motivo deve ter pelo menos 10 caracteres',
            'formData.plan_name.required' => 'Informe o nome do plano',
            'formData.amount.required' => 'Informe o valor da assinatura',
            'formData.amount.min' => 'O valor deve ser maior ou igual a zero',
            'formData.billing_day.min' => 'O dia de cobrança deve ser entre 1 e 28',
            'formData.billing_day.max' => 'O dia de cobrança deve ser entre 1 e 28',
        ];
    }

    /**
     * Montar componente
     */
    public function mount(): void
    {
        Log::info('FilhoSubscription::mount - Iniciando componente', [
            'filho_id' => $this->filho->id,
            'viewMode' => $this->viewMode,
        ]);
        
        $this->loadSubscription();
        $this->formData['start_date'] = now()->format('Y-m-d');
    }

    /**
     * Carregar dados da assinatura
     */
    public function loadSubscription(): void
    {
        $this->filho->load([
            'subscription',
            'subscriptions' => fn($q) => $q->latest()->limit(5),
            'invoices' => fn($q) => $q->where('type', 'subscription')->latest()->limit(12),
        ]);

        $this->subscription = $this->filho->subscription;
        
        Log::info('FilhoSubscription::loadSubscription', [
            'has_subscription' => !is_null($this->subscription),
            'subscription_id' => $this->subscription?->id,
        ]);
    }

    /**
     * Computed: Faturas filtradas
     */
    #[Computed]
    public function invoices()
    {
        $query = $this->filho->invoices()->where('type', 'subscription');

        return match($this->invoiceFilter) {
            'open' => $query->where('status', 'open')->latest()->get(),
            'paid' => $query->where('status', 'paid')->latest()->get(),
            'overdue' => $query->where('status', 'overdue')->latest()->get(),
            default => $query->latest()->get(),
        };
    }

    /**
     * Computed: Estatísticas da assinatura
     */
    #[Computed]
    public function stats()
    {
        $invoices = $this->filho->invoices()->where('type', 'subscription');
        
        return [
            'total_invoices' => $invoices->count(),
            'paid_invoices' => $invoices->where('status', 'paid')->count(),
            'open_invoices' => $invoices->where('status', 'open')->count(),
            'overdue_invoices' => $invoices->where('status', 'overdue')->count(),
            'total_paid' => $invoices->where('status', 'paid')->sum('total_amount'),
            'total_pending' => $invoices->whereIn('status', ['open', 'overdue'])->sum('total_amount'),
            'next_billing' => $this->subscription?->next_billing_date,
            'payment_rate' => $invoices->count() > 0 
                ? round(($invoices->where('status', 'paid')->count() / $invoices->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Mudar para modo de criação
     */
    public function showCreateForm(): void
    {
        Log::info('FilhoSubscription::showCreateForm - CHAMADO', [
            'viewMode_antes' => $this->viewMode,
        ]);
        
        $this->viewMode = 'create';
        $this->formData = [
            'plan_name' => 'Mensalidade Mãos Estendidas',
            'amount' => 350.00,
            'billing_cycle' => 'monthly',
            'billing_day' => 10,
            'start_date' => now()->format('Y-m-d'),
        ];
        
        Log::info('FilhoSubscription::showCreateForm - CONCLUÍDO', [
            'viewMode_depois' => $this->viewMode,
            'formData' => $this->formData,
        ]);
        
        // Disparar evento JavaScript para debug
        $this->dispatch('viewModeChanged', viewMode: 'create');
    }

    /**
     * Mudar para modo de edição
     */
    public function showEditForm(): void
    {
        Log::info('FilhoSubscription::showEditForm - CHAMADO', [
            'has_subscription' => !is_null($this->subscription),
        ]);
        
        if (!$this->subscription) {
            session()->flash('error', 'Nenhuma assinatura encontrada.');
            Log::warning('FilhoSubscription::showEditForm - Sem assinatura');
            return;
        }

        $this->viewMode = 'edit';
        $this->formData = [
            'plan_name' => $this->subscription->plan_name,
            'amount' => $this->subscription->amount,
            'billing_cycle' => $this->subscription->billing_cycle,
            'billing_day' => $this->subscription->billing_day,
            'start_date' => $this->subscription->started_at->format('Y-m-d'),
        ];
        
        Log::info('FilhoSubscription::showEditForm - CONCLUÍDO', [
            'viewMode' => $this->viewMode,
        ]);
        
        $this->dispatch('viewModeChanged', viewMode: 'edit');
    }

    /**
     * Cancelar e voltar para listagem
     */
    public function cancelForm(): void
    {
        Log::info('FilhoSubscription::cancelForm - CHAMADO', [
            'viewMode_antes' => $this->viewMode,
        ]);
        
        $this->viewMode = 'list';
        $this->resetValidation();
        
        Log::info('FilhoSubscription::cancelForm - CONCLUÍDO', [
            'viewMode_depois' => $this->viewMode,
        ]);
        
        $this->dispatch('viewModeChanged', viewMode: 'list');
    }

    /**
     * Salvar assinatura (criar ou editar)
     */
    public function saveSubscription(): void
    {
        Log::info('FilhoSubscription::saveSubscription - INICIANDO', [
            'viewMode' => $this->viewMode,
            'formData' => $this->formData,
        ]);
        
        $this->validate([
            'formData.plan_name' => 'required|string|max:100',
            'formData.amount' => 'required|numeric|min:0|max:99999.99',
            'formData.billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'formData.billing_day' => 'required|integer|min:1|max:28',
            'formData.start_date' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            $startDate = $this->formData['start_date'] 
                ? \Carbon\Carbon::parse($this->formData['start_date'])
                : now();

            $data = [
                'plan_name' => $this->formData['plan_name'],
                'amount' => $this->formData['amount'],
                'billing_cycle' => $this->formData['billing_cycle'],
                'billing_day' => $this->formData['billing_day'],
                'started_at' => $startDate,
            ];

            if ($this->viewMode === 'edit') {
                $this->subscription->update($data);
                session()->flash('success', 'Assinatura atualizada com sucesso!');
                Log::info('FilhoSubscription::saveSubscription - Assinatura atualizada');
            } else {
                $data['first_billing_date'] = $startDate->copy()->addDays(30);
                $data['next_billing_date'] = $startDate->copy()->addDays(30);
                $data['status'] = 'active';
                $data['approved_by_user_id'] = auth()->id();
                
                $this->filho->subscriptions()->create($data);
                session()->flash('success', 'Assinatura criada com sucesso!');
                Log::info('FilhoSubscription::saveSubscription - Assinatura criada');
            }

            $this->viewMode = 'list';
            $this->loadSubscription();
            $this->dispatch('subscription-updated');
            $this->dispatch('viewModeChanged', viewMode: 'list');

        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao salvar assinatura: ' . $e->getMessage());
            Log::error('FilhoSubscription::saveSubscription - ERRO', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Pausar assinatura
     */
    public function pauseSubscription(): void
    {
        if (!$this->subscription || $this->subscription->status !== 'active') {
            session()->flash('error', 'Apenas assinaturas ativas podem ser pausadas.');
            $this->showPauseConfirm = false;
            return;
        }

        try {
            $this->subscription->pause($this->pauseReason);
            
            $this->reset('pauseReason', 'showPauseConfirm');
            $this->loadSubscription();
            
            session()->flash('success', 'Assinatura pausada com sucesso!');
            $this->dispatch('subscription-updated');

        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao pausar assinatura: ' . $e->getMessage());
            Log::error('FilhoSubscription::pauseSubscription - ERRO', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Retomar assinatura pausada
     */
    public function resumeSubscription(): void
    {
        if (!$this->subscription || $this->subscription->status !== 'paused') {
            session()->flash('error', 'Apenas assinaturas pausadas podem ser retomadas.');
            $this->showResumeConfirm = false;
            return;
        }

        try {
            $this->subscription->reactivate();
            
            $this->showResumeConfirm = false;
            $this->loadSubscription();
            
            session()->flash('success', 'Assinatura retomada com sucesso!');
            $this->dispatch('subscription-updated');

        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao retomar assinatura: ' . $e->getMessage());
            Log::error('FilhoSubscription::resumeSubscription - ERRO', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Cancelar assinatura
     */
    public function cancelSubscription(): void
    {
        if (empty($this->cancellationReason) || strlen($this->cancellationReason) < 10) {
            session()->flash('error', 'O motivo do cancelamento deve ter pelo menos 10 caracteres.');
            return;
        }

        if (!$this->subscription) {
            session()->flash('error', 'Nenhuma assinatura ativa encontrada.');
            $this->showCancelConfirm = false;
            return;
        }

        try {
            $this->subscription->cancel($this->cancellationReason);
            
            $this->reset('cancellationReason', 'showCancelConfirm');
            $this->loadSubscription();
            
            session()->flash('success', 'Assinatura cancelada com sucesso!');
            $this->dispatch('subscription-updated');

        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cancelar assinatura: ' . $e->getMessage());
            Log::error('FilhoSubscription::cancelSubscription - ERRO', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Gerar fatura manualmente
     */
    public function generateInvoice(): void
    {
        if (!$this->subscription || $this->subscription->status !== 'active') {
            session()->flash('error', 'Apenas assinaturas ativas podem gerar faturas.');
            return;
        }

        try {
            $invoice = $this->subscription->generateInvoice();
            
            $this->loadSubscription();
            
            session()->flash('success', 'Fatura gerada com sucesso! Número: ' . $invoice->invoice_number);
            $this->dispatch('subscription-updated');

        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao gerar fatura: ' . $e->getMessage());
            Log::error('FilhoSubscription::generateInvoice - ERRO', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Listener para recarregar dados
     */
    #[On('refresh-subscription')]
    public function refresh(): void
    {
        $this->loadSubscription();
    }

    /**
     * Renderizar componente
     */
    public function render()
    {    
        return view('livewire.admin.filhos.filho-subscription');
    }
}
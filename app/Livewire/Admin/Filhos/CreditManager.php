<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use App\Models\CreditTransaction;
use App\Services\CreditService;
use Livewire\Component;
use Livewire\WithPagination;

class CreditManager extends Component
{
    use WithPagination;

    public Filho $filho;
    
    // Formulário de ajuste
    public string $adjustmentType = 'credit';
    public float $amount = 0;
    public string $reason = '';
    
    // Modal
    public bool $showAdjustModal = false;
    public bool $showLimitModal = false;
    public float $newCreditLimit = 0;

    protected $rules = [
        'amount' => 'required|numeric|min:0.01|max:10000',
        'reason' => 'required|string|min:5|max:500',
    ];

    public function mount(Filho $filho): void
    {
        $this->filho = $filho;
        $this->newCreditLimit = $filho->credit_limit;
    }

    public function openAdjustModal(): void
    {
        $this->reset(['amount', 'reason', 'adjustmentType']);
        $this->showAdjustModal = true;
    }

    public function closeAdjustModal(): void
    {
        $this->showAdjustModal = false;
    }

    public function openLimitModal(): void
    {
        $this->newCreditLimit = $this->filho->credit_limit;
        $this->showLimitModal = true;
    }

    public function closeLimitModal(): void
    {
        $this->showLimitModal = false;
    }

    public function submitAdjustment(): void
    {
        $this->validate();

        $creditService = app(CreditService::class);

        if ($this->adjustmentType === 'credit') {
            $creditService->addCredit(
                filho: $this->filho,
                amount: $this->amount,
                reason: $this->reason,
                userId: auth()->id()
            );
            session()->flash('message', "Crédito de R$ {$this->amount} adicionado com sucesso!");
        } else {
            if ($this->amount > $this->filho->credit_available) {
                $this->addError('amount', 'Valor maior que o crédito disponível');
                return;
            }
            
            $creditService->debitCredit(
                filho: $this->filho,
                amount: $this->amount,
                reason: $this->reason,
                userId: auth()->id()
            );
            session()->flash('message', "Débito de R$ {$this->amount} realizado com sucesso!");
        }

        $this->filho->refresh();
        $this->closeAdjustModal();
    }

    public function updateCreditLimit(): void
    {
        $this->validate([
            'newCreditLimit' => 'required|numeric|min:0|max:50000',
        ]);

        $oldLimit = $this->filho->credit_limit;
        
        $this->filho->update([
            'credit_limit' => $this->newCreditLimit,
        ]);

        // Registrar no log
        CreditTransaction::create([
            'filho_id' => $this->filho->id,
            'type' => 'limit_change',
            'amount' => 0,
            'balance_before' => $oldLimit,
            'balance_after' => $this->newCreditLimit,
            'description' => "Limite alterado de R$ {$oldLimit} para R$ {$this->newCreditLimit}",
            'created_by_user_id' => auth()->id(),
        ]);

        $this->filho->refresh();
        session()->flash('message', 'Limite de crédito atualizado com sucesso!');
        $this->closeLimitModal();
    }

    public function toggleBlock(): void
    {
        $this->filho->update([
            'is_blocked' => !$this->filho->is_blocked,
        ]);

        $status = $this->filho->is_blocked ? 'bloqueado' : 'desbloqueado';
        session()->flash('message', "Filho {$status} com sucesso!");
        
        $this->filho->refresh();
    }

    public function render()
    {
        $transactions = $this->filho->creditTransactions()
            ->with('createdByUser')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.admin.filhos.credit-manager', [
            'transactions' => $transactions,
        ]);
    }
}
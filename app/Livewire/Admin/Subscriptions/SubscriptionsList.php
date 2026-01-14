<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Models\Subscription;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionsList extends Component
{
    use WithPagination;

    // Propriedades de filtro
    public string $search = '';
    public string $status = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Listeners
    protected $listeners = ['refreshSubscriptions' => '$refresh'];

    /**
     * Resetar paginação quando filtros mudarem
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Ordenar por campo
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Pausar assinatura
     */
    public function pauseSubscription(string $subscriptionId): void
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);
            
            $subscription->update([
                'status' => 'paused',
                'paused_at' => now(),
                'status_reason' => 'Pausada pelo administrador',
            ]);

            session()->flash('success', 'Assinatura pausada com sucesso!');
            $this->dispatch('refreshSubscriptions');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao pausar assinatura: ' . $e->getMessage());
        }
    }

    /**
     * Retomar assinatura
     */
    public function resumeSubscription(string $subscriptionId): void
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);
            
            $subscription->update([
                'status' => 'active',
                'paused_at' => null,
                'status_reason' => null,
            ]);

            session()->flash('success', 'Assinatura retomada com sucesso!');
            $this->dispatch('refreshSubscriptions');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao retomar assinatura: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar assinatura
     */
    public function cancelSubscription(string $subscriptionId, string $reason = ''): void
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);
            
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'status_reason' => $reason ?: 'Cancelada pelo administrador',
            ]);

            session()->flash('success', 'Assinatura cancelada com sucesso!');
            $this->dispatch('refreshSubscriptions');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cancelar assinatura: ' . $e->getMessage());
        }
    }

    /**
     * Renderizar componente
     */
    public function render()
    {
        $query = Subscription::query()
            ->with(['filho.user']);

        // Filtro por busca (nome do filho, CPF)
        if ($this->search) {
            $query->whereHas('filho', function ($q) {
                $q->where(function ($sub) {
                    // Busca na tabela FILHOS (cpf e phone)
                    $sub->where('cpf', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');

                    // Busca na tabela USERS (name e email) através do relacionamento 'user'
                    $sub->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'ilike', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                });
            });
        }

        // Filtro por status
        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Ordenação
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginação
        $subscriptions = $query->paginate(15);

        // Stats para a view
        $stats = [
            'active' => Subscription::where('status', 'active')->count(),
            'paused' => Subscription::where('status', 'paused')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'total_revenue' => Subscription::where('status', 'active')->sum('amount'),
        ];

        return view('livewire.admin.subscriptions.subscriptions-list', [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
        ]);
    }
}
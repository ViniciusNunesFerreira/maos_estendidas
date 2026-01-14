<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use App\Services\Filho\FilhoService;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalQueue extends Component
{
    use WithPagination;

    public string $search = '';
    public string $orderBy = 'created_at';
    public string $orderDirection = 'asc';

    protected $queryString = ['search'];

    protected FilhoService $filhoService;

    public function boot(FilhoService $filhoService)
    {
        $this->filhoService = $filhoService;
    }

    
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function approve(string $filhoId): void
    {
        $filho = Filho::findOrFail($filhoId);
        
       /*$filho->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);*/

        $this->filhoService->approve($filho);

        session()->flash('message', "{$filho->name} foi aprovado com sucesso!");
        
        $this->dispatch('filhoApproved');
    }

    public function reject(string $filhoId, string $reason): void
    {
        $filho = Filho::findOrFail($filhoId);
        
        $filho->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
        ]);

        session()->flash('message', "{$filho->name} foi rejeitado.");
        
        $this->dispatch('filhoRejected');
    }

    public function render()
    {
        $pendingFilhos = Filho::query()
            ->where('status', 'inactive')
            ->when($this->search, function ($query) {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('cpf', 'like', "%{$this->search}%");
            })
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate(10);

        return view('livewire.admin.filhos.approval-queue', [
            'pendingFilhos' => $pendingFilhos,
        ]);
    }
}
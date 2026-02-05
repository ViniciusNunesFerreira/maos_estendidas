<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use Livewire\Component;
use Livewire\WithPagination;

class FilhosList extends Component
{
    use WithPagination;
    
    // Filtros
    public $search = '';
    public $status = '';
    public $orderBy = 'created_at';
    public $orderDirection = 'desc';
    public $perPage = 15;
    
    // Seleção múltipla
    public $selectedFilhos = [];
    public $selectAll = false;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'orderBy' => ['except' => 'created_at'],
        'orderDirection' => ['except' => 'desc'],
    ];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatus()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->orderBy === $field) {
            $this->orderDirection = $this->orderDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orderBy = $field;
            $this->orderDirection = 'asc';
        }
    }
    
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedFilhos = $this->filhos->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedFilhos = [];
        }
    }
    
    public function bulkAction($action)
    {
        if (empty($this->selectedFilhos)) {
            session()->flash('error', 'Nenhum filho selecionado.');
            return;
        }
        
        switch ($action) {
            case 'activate':
                Filho::whereIn('id', $this->selectedFilhos)->update(['status' => 'active']);
                session()->flash('success', count($this->selectedFilhos) . ' filhos ativados.');
                break;
                
            case 'block':
                Filho::whereIn('id', $this->selectedFilhos)->update(['status' => 'blocked']);
                session()->flash('success', count($this->selectedFilhos) . ' filhos bloqueados.');
                break;
                
            case 'export':
                return $this->exportSelected();
        }
        
        $this->selectedFilhos = [];
        $this->selectAll = false;
    }
    
    public function exportSelected()
    {
        // Lógica de exportação Excel/PDF
        return response()->download(/* ... */);
    }
    
    public function getFilhosProperty()
    {
       return Filho::query()
        ->with(['user']) 
        // 1. Filtro de Busca (Nome, Email ou CPF)
        ->when($this->search, function ($query) {
            $searchTerm = '%' . $this->search . '%';
            $rawCpf = '%' . preg_replace('/[^0-9]/', '', $this->search) . '%';

            // É CRUCIAL envolver os ORs em uma função anônima para isolar a lógica
            $query->where(function ($q) use ($searchTerm, $rawCpf) {
                // Busca direta na tabela de filhos (PgSql ilike para case-insensitive)
                $q->where('cpf', 'ilike', $rawCpf)
                
                // Busca na tabela relacionada 'users'
                ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where(function ($uq) use ($searchTerm) {
                        $uq->where('name', 'ilike', $searchTerm)
                           ->orWhere('email', 'ilike', $searchTerm);
                    });
                });
            });
        })
        // 2. Filtro de Status (Isolado da busca)
        ->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })
        // 3. Ordenação e Paginação
        ->orderBy($this->orderBy, $this->orderDirection)
        ->paginate($this->perPage);
    }
    
    public function render()
    {
        return view('livewire.admin.filhos.filhos-list', [
            'filhos' => $this->filhos,
        ]);
    }
}
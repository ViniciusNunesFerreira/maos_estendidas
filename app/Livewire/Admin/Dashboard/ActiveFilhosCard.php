<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Filho;
use Livewire\Component;

class ActiveFilhosCard extends Component
{
    public int $activeFilhos = 0;
    public int $pendingApproval = 0;
    public int $blockedFilhos = 0;
    public int $totalFilhos = 0;

    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->totalFilhos = Filho::count();
        $this->activeFilhos = Filho::where('status', 'active')->count();
        $this->pendingApproval = Filho::where('status', 'pending')->count();
        $this->blockedFilhos = Filho::where('is_blocked_by_debt', true)->count();
    }

    public function render()
    {
        return view('livewire.admin.dashboard.active-filhos-card');
    }
}
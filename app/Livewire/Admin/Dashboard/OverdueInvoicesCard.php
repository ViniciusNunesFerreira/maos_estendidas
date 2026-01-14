<?php
// app/Livewire/Admin/Dashboard/OverdueInvoicesCard.php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Invoice;
use Livewire\Component;

class OverdueInvoicesCard extends Component
{
    public int $overdueCount = 0;
    public float $overdueAmount = 0;
    public int $dueThisWeek = 0;
    public float $trend = 0;

    protected $listeners = ['refreshDashboard' => 'loadData'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        // Faturas vencidas
        $this->overdueCount = Invoice::where('status', 'pending')
            ->where('due_date', '<', today())
            ->count();

        // Valor total vencido
        $this->overdueAmount = Invoice::where('status', 'pending')
            ->where('due_date', '<', today())
            ->sum('total_amount');

        // Vencem esta semana
        $this->dueThisWeek = Invoice::where('status', 'pending')
            ->whereBetween('due_date', [today(), today()->addWeek()])
            ->count();

        // Tendência (comparar com mês passado)
        $lastMonthOverdue = Invoice::where('status', 'pending')
            ->where('due_date', '<', today()->subMonth())
            ->count();

        if ($lastMonthOverdue > 0) {
            $this->trend = round((($this->overdueCount - $lastMonthOverdue) / $lastMonthOverdue) * 100, 1);
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.overdue-invoices-card');
    }
}
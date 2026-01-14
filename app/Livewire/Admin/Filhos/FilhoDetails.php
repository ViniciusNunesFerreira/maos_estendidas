<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use Livewire\Component;

class FilhoDetails extends Component
{
    public Filho $filho;
    public array $stats = [];

    public function mount(Filho $filho): void
    {
        $this->filho = $filho;
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $this->stats = [
            'total_orders' => $this->filho->orders()->count(),
            'completed_orders' => $this->filho->orders()->where('status', 'completed')->count(),
            'total_spent' => $this->filho->orders()->where('status', 'completed')->sum('total'),
            'average_ticket' => $this->filho->orders()->where('status', 'completed')->avg('total') ?? 0,
            'this_month_spent' => $this->filho->orders()
                ->whereMonth('created_at', now()->month)
                ->where('status', 'completed')
                ->sum('total'),
            'pending_invoices' => $this->filho->invoices()->where('status', 'pending')->count(),
            'overdue_invoices' => $this->filho->invoices()->where('status', 'overdue')->count(),
            'total_invoices_amount' => $this->filho->invoices()
                ->whereIn('status', ['pending', 'overdue'])
                ->sum('amount'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.filhos.filho-details');
    }
}
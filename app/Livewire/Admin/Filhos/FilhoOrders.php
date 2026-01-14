<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use Livewire\Component;
use Livewire\WithPagination;

class FilhoOrders extends Component
{
    use WithPagination;

    public Filho $filho;
    public string $statusFilter = '';
    public string $periodFilter = '';

    protected $queryString = ['statusFilter', 'periodFilter'];

    public function mount(Filho $filho): void
    {
        $this->filho = $filho;
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->filho->orders()
            ->with(['items.product'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->periodFilter, function ($q) {
                match ($this->periodFilter) {
                    'today' => $q->whereDate('created_at', today()),
                    'week' => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                    'month' => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                    'year' => $q->whereYear('created_at', now()->year),
                    default => null
                };
            })
            ->orderByDesc('created_at');

        $orders = $query->paginate(15);

        $summary = [
            'total_orders' => $this->filho->orders()->count(),
            'total_spent' => $this->filho->orders()->where('status', 'completed')->sum('total'),
            'this_month' => $this->filho->orders()
                ->whereMonth('created_at', now()->month)
                ->where('status', 'completed')
                ->sum('total'),
            'average_ticket' => $this->filho->orders()->where('status', 'completed')->avg('total') ?? 0,
        ];

        return view('livewire.admin.filhos.filho-orders', [
            'orders' => $orders,
            'summary' => $summary,
        ]);
    }
}
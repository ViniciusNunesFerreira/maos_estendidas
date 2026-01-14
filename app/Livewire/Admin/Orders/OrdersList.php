<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $originFilter = '';
    public string $periodFilter = 'today';
    public string $customerType = '';

    protected $queryString = ['search', 'statusFilter', 'originFilter', 'periodFilter', 'customerType'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Order::query()
            ->with(['filho', 'items'])
            ->when($this->search, fn($q) => 
                $q->where('order_number', 'ilike', "%{$this->search}%")
                  ->orWhere('guest_name', 'ilike', "%{$this->search}%")
                  ->orWhereHas('filho', fn($fq) => 
                      $fq->where('name', 'ilike', "%{$this->search}%")
                  )
            )
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->originFilter, fn($q) => $q->where('origin', $this->originFilter))
            ->when($this->customerType === 'filho', fn($q) => $q->whereNotNull('filho_id'))
            ->when($this->customerType === 'guest', fn($q) => $q->whereNull('filho_id'))
            ->when($this->periodFilter, function ($q) {
                match ($this->periodFilter) {
                    'today' => $q->whereDate('created_at', today()),
                    'yesterday' => $q->whereDate('created_at', today()->subDay()),
                    'week' => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                    'month' => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                    'all' => null,
                    default => null
                };
            })
            ->orderByDesc('created_at');

        $orders = $query->paginate(20);

        $stats = [
            'total' => (clone $query)->count(),
            'total_revenue' => (clone $query)->where('status', '!=', 'cancelled')->sum('total'),
            'pending' => Order::whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'completed_today' => Order::whereDate('created_at', today())->where('status', 'completed')->count(),
        ];

        return view('livewire.admin.orders.orders-list', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }
}
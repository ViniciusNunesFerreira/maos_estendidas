<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

class InvoicesList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $periodFilter = '';

    protected $queryString = ['search', 'statusFilter', 'typeFilter', 'periodFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $invoices = Invoice::query()
            ->with(['filho'])
            ->when($this->search, fn($q) => 
                $q->whereHas('filho', fn($fq) => 
                    $fq->where('name', 'ilike', "%{$this->search}%")
                       ->orWhere('cpf', 'like', "%{$this->search}%")
                )
                ->orWhere('invoice_number', 'ilike', "%{$this->search}%")
            )
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->when($this->periodFilter, function ($q) {
                match ($this->periodFilter) {
                    'this_month' => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                    'last_month' => $q->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year),
                    'overdue' => $q->where('status', 'overdue'),
                    default => null
                };
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total_pending' => Invoice::where('status', 'pending')->sum('total_amount'),
            'total_overdue' => Invoice::where('status', 'overdue')->sum('total_amount'),
            'paid_this_month' => Invoice::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('total_amount'),
            'pending_count' => Invoice::where('status', 'pending')->count(),
            'overdue_count' => Invoice::where('status', 'overdue')->count(),
        ];

        return view('livewire.admin.invoices.invoices-list', [
            'invoices' => $invoices,
            'stats' => $stats,
        ]);
    }
}
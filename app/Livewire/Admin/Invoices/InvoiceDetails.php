<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use Livewire\Component;

class InvoiceDetails extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load(['filho.user', 'items.product', 'payments']);
    }

    public function sendReminder(): void
    {
        $this->invoice->filho->user->notify(
            new \App\Notifications\InvoiceReminder($this->invoice)
        );
        
        $this->invoice->update(['last_reminder_at' => now()]);
        
        session()->flash('message', 'Lembrete enviado com sucesso!');
    }

    public function markAsPaid(): void
    {
        $this->invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        
        session()->flash('message', 'Fatura marcada como paga!');
        $this->invoice->refresh();
    }

    public function cancel(): void
    {
        $this->invoice->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        session()->flash('message', 'Fatura cancelada!');
        $this->invoice->refresh();
    }

    public function render()
    {
        return view('livewire.admin.invoices.invoice-details');
    }
}
<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Livewire\Component;
use Livewire\Attributes\On;

class PaymentRegister extends Component
{
    public ?Invoice $invoice = null;
    
    public string $amount = '';
    public string $method = 'dinheiro';
    public string $internal_notes = '';
    public string $reference = '';
    
    // Modal de Pagamento
    public bool $showPaymentModal = false;

    protected function rules(): array
    {
       
        return [
            'amount' => 'required',
            'method' => 'required|in:pix,credito,debito,dinheiro',
            'internal_notes' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
        ];
    }

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice && $invoice->exists) {
            $this->loadInvoice($invoice);
        }
       
    }

    public function setFullAmount(): void
    {
        $this->amount = number_format($this->invoice->total_amount - $this->invoice->paid_amount, 2, '.', '');
    }

    #[On('prepare-payment')] 
    public function loadInvoice($invoiceId): void
    {
        $this->invoice = Invoice::findOrFail($invoiceId);
        
        // Reinicia os campos para a nova invoice selecionada
        $this->amount =  '';
        $this->internal_notes = '';
        $this->reference = '';
        
        $this->showPaymentModal = true;
        $this->dispatch('open-modal', 'payment-modal');
    }

    private function convertBrlToFloat($value)
    {
        if (empty($value)) return 0.0;

        $onlyNumbers = preg_replace('/\D/', '', $value);

        return (float) ($onlyNumbers / 100);
    }


    public function save(): void
    {

        $valorConvertido = $this->convertBrlToFloat($this->amount);
        $this->amount = $valorConvertido;
        $remaining = $this->invoice->remaining_amount;

        $this->validate([
            'amount' => "required|numeric|min:0|max:{$remaining}",
            'method' => 'required|in:pix,credito,debito,dinheiro',
            'internal_notes' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
        ]);


        $invoiceService = app(InvoiceService::class);

        $invoiceService->registerManualPayment($this->invoice, [
            'amount'         => $this->amount,
            'method'         => $this->method,
            'internal_notes' => $this->internal_notes,
            'reference'      => $this->reference,
        ]);

        
        $this->invoice->refresh();
        
        $this->showPaymentModal = false;

        $this->dispatch('close-modal', 'payment-modal');

        session()->flash('message', 'Pagamento registrado com sucesso!');
        
        // Se pagou tudo, redirecionar
        if ($this->invoice->status === 'paid') {
            $this->redirect(route('admin.invoices.show', $this->invoice));
        } else {
            $this->amount = $this->invoice->amount - $this->invoice->amount_paid;
            $this->notes = '';
            $this->reference = '';
        }
    }

    public function render()
    {
        return view('livewire.admin.invoices.payment-register');
    }
}
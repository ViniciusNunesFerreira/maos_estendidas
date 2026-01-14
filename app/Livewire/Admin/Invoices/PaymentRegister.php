<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Livewire\Component;

class PaymentRegister extends Component
{
    public Invoice $invoice;
    
    public float $amount = 0;
    public string $method = 'pix';
    public string $notes = '';
    public string $reference = '';

    protected function rules(): array
    {
        $remaining = $this->invoice->amount - $this->invoice->amount_paid;
        
        return [
            'amount' => "required|numeric|min:0.01|max:{$remaining}",
            'method' => 'required|in:pix,credito,debito,dinheiro,transferencia,boleto',
            'notes' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
        ];
    }

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
        $this->amount = $invoice->amount - $invoice->amount_paid;
    }

    public function setFullAmount(): void
    {
        $this->amount = $this->invoice->amount - $this->invoice->amount_paid;
    }

    public function save(): void
    {
        $this->validate();

        $invoiceService = app(InvoiceService::class);
        
        $invoiceService->registerPayment(
            invoice: $this->invoice,
            amount: $this->amount,
            method: $this->method,
            notes: $this->notes,
            reference: $this->reference,
            userId: auth()->id()
        );

        session()->flash('message', 'Pagamento registrado com sucesso!');
        
        $this->invoice->refresh();
        
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
        $paymentMethods = [
            'pix' => 'PIX',
            'credito' => 'Cartão de Crédito',
            'debito' => 'Cartão de Débito',
            'dinheiro' => 'Dinheiro',
            'transferencia' => 'Transferência Bancária',
            'boleto' => 'Boleto',
        ];

        return view('livewire.admin.invoices.payment-register', [
            'paymentMethods' => $paymentMethods,
            'remaining' => $this->invoice->amount - $this->invoice->amount_paid,
        ]);
    }
}
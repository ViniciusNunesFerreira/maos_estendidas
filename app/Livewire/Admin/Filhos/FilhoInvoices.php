<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class FilhoInvoices extends Component
{
    use WithPagination;

    public Filho $filho;
    
    // Filtros
    public string $filterStatus = '';
    public string $filterYear = '';
    public string $filterMonth = '';
    public string $sortBy = 'due_date_desc';
    
    // Modal de Pagamento
    public bool $showPaymentModal = false;
    public ?Invoice $selectedInvoice = null;
    public float $paymentAmount = 0;
    public string $paymentMethod = 'pix';

    // Modal de Geração de Fatura
    public bool $showGenerateModal = false;
    public string $newInvoiceType = 'subscription'; 
    public ?string $newReferenceMonth = null;
    public ?string $newPeriodStart = null;
    public ?string $newPeriodEnd = null;
    public ?string $newDueDate = null;
    public float $newAmount = 0;
    public string $newDescription = '';

    protected $queryString = [
        'filterStatus' => ['except' => ''],
        'filterYear' => ['except' => ''],
        'filterMonth' => ['except' => ''],
    ];

    public function mount(Filho $filho): void
    {
        $this->filho = $filho;
        $this->resetGenerateForm();
    }

    // --- Listeners e Ações de Modal ---

    #[On('generateInvoice')] 
    public function openGenerateInvoiceModal()
    {
        $this->resetGenerateForm();
        $this->showGenerateModal = true;
        // Opcional: Forçar reset de erros
        $this->resetValidation();
        $this->dispatch('open-modal', 'generate-invoice-modal');
    }

    public function resetGenerateForm(): void
    {
        $this->newInvoiceType = 'subscription';
        $this->newReferenceMonth = now()->format('Y-m');
        $this->newPeriodStart = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->newPeriodEnd = now()->subMonth()->endOfMonth()->format('Y-m-d');
        $this->newDueDate = now()->addDays(5)->format('Y-m-d');
        $this->newAmount = $this->filho->mensalidade_amount ?? 0;
        $this->newDescription = '';
        $this->resetValidation();
    }

    // --- Lógica de Salvamento ---

    public function saveInvoice(): void
    {
        // Validação dinâmica baseada no tipo
        $rules = [
            'newInvoiceType' => 'required|in:subscription,consumption,manual',
            'newDueDate' => 'required|date',
        ];

        if ($this->newInvoiceType === 'subscription') {
            $rules['newReferenceMonth'] = 'required';
            $rules['newAmount'] = 'required|numeric|min:0.01';
        } elseif ($this->newInvoiceType === 'consumption') {
            $rules['newPeriodStart'] = 'required|date';
            $rules['newPeriodEnd'] = 'required|date|after_or_equal:newPeriodStart';
        } elseif ($this->newInvoiceType === 'manual') {
            $rules['newAmount'] = 'required|numeric|min:0.01';
            $rules['newDescription'] = 'required|string|min:3';
        }

        $this->validate($rules);

        try {
            DB::beginTransaction();

            if ($this->newInvoiceType === 'subscription') {
                $this->createSubscriptionInvoice();
            } elseif ($this->newInvoiceType === 'consumption') {
                if (!$this->createConsumptionInvoice()) {
                    DB::rollBack();
                    return; 
                }
            } elseif ($this->newInvoiceType === 'manual') {
                $this->createManualInvoice();
            }

            DB::commit();
            
            // Sucesso e Fechamento do Modal
            $this->showGenerateModal = false;
            
            // IMPORTANTE: Dispara o evento para o Alpine fechar o modal visualmente
            $this->dispatch('close-modal', 'generate-invoice-modal');
            $this->dispatch('flash', message: 'Fatura gerada com sucesso!', type: 'success');
            
            session()->flash('message', 'Fatura gerada com sucesso!');
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('newInvoiceType', $e->getMessage());
        }
    }

    // --- Métodos Auxiliares de Criação (Mantidos da versão anterior) ---
    
    private function createSubscriptionInvoice(): void
    {
        $refDate = Carbon::createFromFormat('Y-m', $this->newReferenceMonth)->startOfMonth();

        $subscriptionID =  $this->filho->subscription?->id;
        
        $invoice = Invoice::create([
            'filho_id' => $this->filho->id,
            'invoice_number' => Invoice::generateNextInvoiceNumber('subscription'),
            'type' => 'subscription',
            'subscription_id' => $subscriptionID, 
            'period_start' => $refDate,
            'period_end' => $refDate->copy()->endOfMonth(),
            'issue_date' => now(),
            'due_date' => $this->newDueDate,
            'subtotal' => $this->newAmount,
            'total_amount' => $this->newAmount,
            'status' => 'pending',
            'notes' => "Mensalidade Competência: {$refDate->format('m/Y')} (Gerada Manualmente)",
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Mensalidade - {$refDate->format('F/Y')}",
            'category' => 'Mensalidade',
            'quantity' => 1,
            'unit_price' => $this->newAmount,
            'subtotal' => $this->newAmount,
            'total' => $this->newAmount,
            'purchase_date' => now(),
        ]);
    }

    private function createConsumptionInvoice(): bool
    {
        $startDate = Carbon::parse($this->newPeriodStart)->startOfDay();
        $endDate = Carbon::parse($this->newPeriodEnd)->endOfDay();

        $orders = Order::where('filho_id', $this->filho->id)
            ->eligibleForInvoicing()
            ->where('payment_method_chosen', 'carteira')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['items.product', 'payment'])
            ->get();


        if ($orders->isEmpty()) {
            $this->addError('newPeriodStart', 'Nenhum pedido pendente encontrado neste período.');
            return false;
        }

        $invoice = Invoice::create([
            'filho_id' => $this->filho->id,
            'invoice_number' => Invoice::generateNextInvoiceNumber('consumption'),
            'type' => 'consumption',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'issue_date' => now(),
            'due_date' => $this->newDueDate,
            'status' => 'pending',
            'notes' => "Fechamento Avulso: {$startDate->format('d/m')} a {$endDate->format('d/m')}",
        ]);

        $totalSubtotal = 0;

        foreach ($orders as $order) {
           // $order->update(['invoice_id' => $invoice->id, 'is_invoiced' => true, 'invoiced_at' => now(), 'status' => 'completed']);
            $order->markAsInvoiced($invoice);
            
            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'purchase_date' => $order->created_at,
                    'description' => $item->product_name ?? 'Item',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'total' => $item->total,
                ]);
                $totalSubtotal += $item->subtotal;
            }
        }
        
        $invoice->update(['subtotal' => $totalSubtotal, 'total_amount' => $totalSubtotal]);
        return true;
    }

    private function createManualInvoice(): void
    {
        try{
            $invoice = Invoice::create([
                'filho_id' => $this->filho->id,
                'invoice_number' => Invoice::generateNextInvoiceNumber('consumption'),
                'type' => 'consumption',
                'is_avulse' => true,
                'period_start' => now(),
                'period_end' => now(),
                'issue_date' => now(),
                'due_date' => $this->newDueDate,
                'subtotal' => $this->newAmount,
                'total_amount' => $this->newAmount,
                'status' => 'pending',
                'notes' => $this->newDescription,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $this->newDescription,
                'category' => 'Serviços',
                'quantity' => 1,
                'unit_price' => $this->newAmount,
                'subtotal' => $this->newAmount,
                'total' => $this->newAmount,
                'purchase_date' => now(),
            ]);
        }catch(\Exception $e){
            \Log::debug('Ocorreu erro: '.$e->getMessage());
        }
    }

    public function updatedNewInvoiceType($value)
    {
        $this->resetValidation();
        if ($value === 'subscription') {
            $this->newAmount = $this->filho->mensalidade_amount ?? 0;
        } elseif ($value === 'manual') {
            $this->newAmount = 0;
        }
    }

    // --- Métodos de Pagamento e Render ---
    
    public function openPaymentModal(string $invoiceId): void
    {
        $this->selectedInvoice = Invoice::findOrFail($invoiceId);
        $this->paymentAmount = max(0, $this->selectedInvoice->remaining_amount);
        $this->showPaymentModal = true;
    }

    public function registerPayment(): void
    {
        $this->validate(['paymentAmount' => 'required|numeric|min:0.01']);
        
        if ($this->selectedInvoice) {
            $this->selectedInvoice->markAsPaid($this->paymentAmount);
            $this->showPaymentModal = false;
            $this->dispatch('close-modal', 'payment-modal');
            session()->flash('message', 'Pagamento registrado!');
        }
    }

    public function getMonthName($monthNumber)
    {
        return Carbon::create()->month($monthNumber)->locale('pt_BR')->monthName;
    }

    public function render()
    {
        $query = $this->filho->invoices();

        if ($this->filterStatus) $query->where('status', $this->filterStatus);
        if ($this->filterYear) $query->whereYear('due_date', $this->filterYear);
        if ($this->filterMonth) $query->whereMonth('due_date', $this->filterMonth);

        switch ($this->sortBy) {
            case 'amount_desc': $query->orderBy('total_amount', 'desc'); break;
            case 'amount_asc': $query->orderBy('total_amount', 'asc'); break;
            case 'due_date_asc': $query->orderBy('due_date', 'asc'); break;
            default: $query->orderBy('due_date', 'desc'); break;
        }

        $availableYears = $this->filho->invoices()
            ->selectRaw('EXTRACT(YEAR FROM due_date) as year')
            ->distinct()->pluck('year');
        if($availableYears->isEmpty()) $availableYears = [now()->year];

        // Estatísticas
        $summary = [
            'total' => $this->filho->invoices()->count(),
            'paid' => $this->filho->invoices()->where('status', 'paid')->count(),
            'pending' => $this->filho->invoices()->whereIn('status', ['pending', 'open'])->count(),
            'overdue' => $this->filho->invoices()->where('status', 'overdue')->count(),
        ];

        return view('livewire.admin.filhos.filho-invoices', [
            'invoices' => $query->paginate(10),
            'availableYears' => $availableYears,
            'summary' => $summary
        ]);
    }
}
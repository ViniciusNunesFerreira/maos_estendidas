<?php

namespace App\Livewire\Admin\Filhos;

use Livewire\Component;
use App\Models\Filho;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteFilho extends Component
{
    public Filho $filho;
    public bool $confirmingFilhoDeletion = false;
    public string $confirmationText = '';

    public function confirmDeletion()
    {
        $this->confirmingFilhoDeletion = true;
        $this->confirmationText = ''; // Reseta o texto de confirmação
    }

    public function deleteFilho()
    {
        // Validação de segurança dupla
        if (trim(strtoupper($this->confirmationText)) !== 'EXCLUIR') {
            $this->addError('confirmationText', 'Digite EXCLUIR para confirmar.');
            return;
        }

        DB::beginTransaction();

        try {
            $filhoId = $this->filho->id;
            $userId = $this->filho->user_id;

            // 1. Limpar Pedidos (Orders) e TODOS os Relacionamentos
            $orderIds = Order::where('filho_id', $filhoId)->pluck('id');
            if ($orderIds->isNotEmpty()) {
                OrderItem::whereIn('order_id', $orderIds)->forceDelete();
                PaymentIntent::whereIn('order_id', $orderIds)->forceDelete();
                
                // Mapear Payments para limpar Getnet antes
                $paymentIds = Payment::whereIn('order_id', $orderIds)->pluck('id');
                if ($paymentIds->isNotEmpty()) {
                    // Limpa transações de maquininha vinculadas
                   // DB::table('getnet_transactions')->whereIn('payment_id', $paymentIds)->delete();
                }
                Payment::whereIn('order_id', $orderIds)->forceDelete();

                // Limpar movimentações de caixa vinculadas à ordem
                DB::table('cash_movements')->whereIn('order_id', $orderIds)->delete();

                Order::whereIn('id', $orderIds)->forceDelete();
            }

            // 2. Limpar Faturas (Invoices) e Relacionamentos
            $invoiceIds = Invoice::where('filho_id', $filhoId)->pluck('id');
            if ($invoiceIds->isNotEmpty()) {
                InvoiceItem::whereIn('invoice_id', $invoiceIds)->forceDelete();
                PaymentIntent::whereIn('invoice_id', $invoiceIds)->forceDelete();
                
                // Limpar Getnet vinculada à Fatura
                $invoicePaymentIds = Payment::whereIn('invoice_id', $invoiceIds)->pluck('id');
                if ($invoicePaymentIds->isNotEmpty()) {
                  //  DB::table('getnet_transactions')->whereIn('payment_id', $invoicePaymentIds)->delete();
                }
                Payment::whereIn('invoice_id', $invoiceIds)->forceDelete();
                
                // Assinaturas de faturas (tabela pivô)
                //DB::table('subscription_invoices')->whereIn('invoice_id', $invoiceIds)->delete();
                Invoice::whereIn('id', $invoiceIds)->forceDelete();
            }

            // 3. Limpar Assinaturas, Transações e Logs de Sistema diretos
            Subscription::where('filho_id', $filhoId)->forceDelete();
            Transaction::where('filho_id', $filhoId)->forceDelete();
            
            // CORREÇÃO APLICADA AQUI: Limpa os registros de mensagens motivacionais disparadas para o filho
            DB::table('motivational_logs')->where('filho_id', $filhoId)->delete();

            // 4. Excluir o Filho e o Usuário vinculado definitivamente
            $this->filho->forceDelete();
            
            if ($userId) {
                DB::table('users')->where('id', $userId)->delete();
            }

            DB::commit();

            Log::alert("Filho e dependências excluídos definitivamente.", ['filho_id' => $filhoId, 'admin_id' => auth()->id()]);

            session()->flash('success', 'Registro excluído definitivamente com sucesso.');
            return redirect()->route('admin.filhos.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao excluir filho definitivamente: " . $e->getMessage());
            session()->flash('error', 'Ocorreu um erro ao excluir os dados. Verifique os logs.');
            $this->confirmingFilhoDeletion = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.filhos.delete-filho');
    }
}
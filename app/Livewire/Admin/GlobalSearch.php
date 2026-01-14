<?php

namespace App\Livewire\Admin;

use App\Models\Filho;
use App\Models\Order;
use App\Models\Product;
use App\Models\Invoice;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GlobalSearch extends Component
{
    public string $query = '';
    public array $results = [];
    
    // UI States
    public bool $hasResults = false;
    public int $selectedIndex = -1; // Para navegação via teclado

    public function updatedQuery()
    {
        $this->selectedIndex = -1; // Reseta seleção ao digitar

        if (strlen($this->query) < 2) {
            $this->resetResults();
            return;
        }

        try {
            $term = $this->query;
            $searchTerm = '%' . $term . '%';
            
            // Array unificado de resultados
            $formattedResults = [];

            // 1. BUSCA FILHOS (Com relacionamento User para pegar o nome)
            $filhos = Filho::query()
                ->with('user') // Eager loading essencial
                ->where('cpf', 'ilike', $searchTerm) // Use 'ilike' se for Postgres
                ->orWhereHas('user', function($q) use ($searchTerm) {
                    $q->where('name', 'ilike', $searchTerm);
                })
                ->limit(5)
                ->get();

            foreach ($filhos as $filho) {
                $formattedResults[] = [
                    'type' => 'filho',
                    'type_label' => 'Filho / Aluno',
                    'id' => $filho->id,
                    'title' => $filho->user->name ?? 'Filho sem Nome', // Fallback seguro
                    'subtitle' => 'CPF: ' . $filho->cpf_formatted,
                    'url' => route('admin.filhos.show', $filho->id),
                    'icon' => 'user', // Ícone para UI
                    'status_color' => $filho->status === 'active' ? 'text-green-500' : 'text-gray-400'
                ];
            }

            // 2. BUSCA PRODUTOS
            $products = Product::query()
                ->where('name', 'ilike', $searchTerm)
                ->orWhere('sku', 'ilike', $searchTerm)
                ->limit(5)
                ->get();

            foreach ($products as $product) {
                $formattedResults[] = [
                    'type' => 'product',
                    'type_label' => 'Produto',
                    'id' => $product->id,
                    'title' => $product->name,
                    'subtitle' => 'SKU: ' . $product->sku . ' • R$ ' . number_format($product->price, 2, ',', '.'),
                    'url' => route('admin.products.edit', $product->id),
                    'icon' => 'cube',
                    'status_color' => $product->stock_quantity > 0 ? 'text-blue-500' : 'text-red-500'
                ];
            }

            // 3. BUSCA PEDIDOS
            $orders = Order::query()
                ->where('order_number', 'ilike', $searchTerm)
                ->limit(3)
                ->get();

            foreach ($orders as $order) {
                $formattedResults[] = [
                    'type' => 'order',
                    'type_label' => 'Pedido',
                    'id' => $order->id,
                    'title' => 'Pedido #' . $order->order_number,
                    'subtitle' => 'Total: R$ ' . number_format($order->total ?? 0, 2, ',', '.') . ' • ' . ucfirst($order->status),
                    'url' => route('admin.orders.show', $order->id),
                    'icon' => 'shopping-cart',
                    'status_color' => 'text-gray-500'
                ];
            }

            // 4. BUSCA FATURAS
            $invoices = Invoice::query()
                ->where('invoice_number', 'ilike', $searchTerm)
                ->limit(3)
                ->get();

            foreach ($invoices as $invoice) {
                $formattedResults[] = [
                    'type' => 'invoice',
                    'type_label' => 'Fatura',
                    'id' => $invoice->id,
                    'title' => 'Fatura #' . $invoice->invoice_number,
                    'subtitle' => 'Venc: ' . ($invoice->due_date?->format('d/m/Y') ?? 'N/A') . ' • ' . ucfirst($invoice->status),
                    'url' => route('admin.invoices.show', $invoice->id),
                    'icon' => 'document-text',
                    'status_color' => $invoice->status === 'paid' ? 'text-green-500' : ($invoice->status === 'overdue' ? 'text-red-500' : 'text-yellow-500')
                ];
            }

            $this->results = $formattedResults;
            $this->hasResults = count($this->results) > 0;

        } catch (\Exception $e) {
            Log::error("Global Search Error: " . $e->getMessage());
            $this->resetResults();
        }
    }

    public function clear()
    {
        $this->reset(['query', 'selectedIndex']);
        $this->resetResults();
    }

    private function resetResults()
    {
        $this->results = [];
        $this->hasResults = false;
    }

    public function render()
    {
        return view('livewire.admin.global-search');
    }
}
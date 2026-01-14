<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use App\Services\Filho\FilhoService;
use Livewire\Component;
use Livewire\Attributes\On; // Importante para Livewire 3

class AdjustCreditModal extends Component
{
    public $showModal = false;
    // Inicialize como null para evitar erro de tipagem antes do load
    public ?Filho $filho = null; 
    
    public $credit_limit = ''; // Inicialize como string vazia ou zero
    public $reason = '';

    // Regras podem ficar aqui ou no método validate()
    protected $rules = [
        'credit_limit' => 'required', // Removi numeric aqui pois vem com máscara (R$)
        'reason' => 'required|string|min:5|max:255',
    ];

    #[On('adjustCredit')] 
    public function openModal($filho)
    {
        
        // No Livewire 3, se passar o ID no dispatch, ele tenta resolver o Model.
        // Se vier array, pegamos o ID.
        if (is_array($filho) && isset($filho['filho'])) {
            $id = $filho['filho'];
            $this->filho = Filho::find($id);
        } elseif ($filho instanceof Filho) {
            $this->filho = $filho;
        } else {
            $this->filho = Filho::find($filho);
        }

        if ($this->filho) {
            $this->credit_limit = number_format($this->filho->credit_limit, 2, ',', '.'); // Formata para exibir na máscara
            $this->reason = '';
            
            // Abre o modal logicamente se estiver usando wire:model no modal, 
            // mas como seu modal usa evento window, vamos garantir.
            $this->showModal = true; 
        }
    }

    private function convertBrlToFloat($value)
    {
        if (empty($value)) return 0.0;

        // 1. Remove absolutamente tudo que não for número
        // Ex: "R$ 1.380,00" vira "138000"
        $onlyNumbers = preg_replace('/\D/', '', $value);

        // 2. Pegamos o valor e dividimos por 100 para colocar a vírgula no lugar certo
        // Isso garante que os últimos dois dígitos sejam sempre os centavos
        // Ex: "138000" vira 1380.00
        return (float) ($onlyNumbers / 100);
    }

    public function save(FilhoService $service)
    {

        // Converte usando a lógica de centavos
        $valorConvertido = $this->convertBrlToFloat($this->credit_limit);
        $this->credit_limit = $valorConvertido;

        $this->validate([
            'credit_limit' => 'required|numeric|min:0',
            'reason' => 'required|string|min:5',
        ]);

        try {
            // Se o service espera float, garanta o type cast
            $service->adjustCreditLimit($this->filho, (float) $this->credit_limit);
            
            $this->dispatch('close-modal', 'modal-ajuste-credito'); 
            
            // 2. Notifica sucesso
            $this->dispatch('flash', message: 'Limite atualizado!', type: 'success');

            // 3. Atualiza componentes pai/irmãos
            $this->dispatch('filhoUpdated'); 
            
            // Reset
            $this->showModal = false;
            $this->reason = '';

        } catch (\Exception $e) {
            $this->dispatch('flash', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.admin.filhos.adjust-credit-modal');
    }
}
<?php

namespace App\Livewire\Admin\Filhos;

use Livewire\Component;
use App\Models\Filho;
use App\Services\Filho\FilhoService;
use Livewire\Attributes\On;

class UpdateStatusFilho extends Component
{
    public $showModal = false;
    public ?Filho $filho = null;
    public $status = ''; 
    public bool $is_blocked_by_debt = false;
    public $reason = '';

    #[On('adjustStatusFilho')] 
    public function openModal($filho, $status)
    {
        
        // Se vier array, pegamos o ID.
        if (is_array($filho) && isset($filho['filho'])) {
            $id = $filho['filho'];
            $this->filho = Filho::find($id);
        } elseif ($filho instanceof Filho) {
            $this->filho = $filho;
        } else {
            $this->filho = Filho::find($filho);
        }

        if(isset($status)){
            $this->status = $status;
        }

        if ($this->filho && $this->status) {                   
            $this->showModal = true; 
        }
    }

    public function save(FilhoService $service)
    {

        if($this->status !== 'active'){
            $this->validate([
                'reason' => 'required|string|min:5',
            ]);
        }

        try {
            
            if($this->status !== 'active'){
                $service->suspend($this->filho, $this->reason, $this->is_blocked_by_debt);
            }else{
                $service->reactivate($this->filho);
            }
             
            $this->dispatch('close-modal', 'modal-status-filho'); 
            
            // 2. Notifica sucesso
            $this->dispatch('flash', message: 'Status Atualizado!', type: 'success');

            // 3. Atualiza componentes pai/irmãos
            $this->dispatch('filhoUpdated'); 
            
            // Reset
            $this->showModal = false;
            $this->reason = '';
            $this->is_blocked_by_debt = false;

        } catch (\Exception $e) {
            $this->dispatch('flash', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.admin.filhos.update-status-filho');
    }
}

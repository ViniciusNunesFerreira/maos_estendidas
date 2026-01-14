<?php

namespace App\Livewire\Admin\Filhos;

use App\Models\Filho;
use App\Services\Filho\FilhoService;
use App\DTOs\CreateFilhoDTO;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;

class FilhoForm extends Component
{
    use WithFileUploads;
    
    public ?Filho $filho = null;
    public $isEditing = false;
    
    // Dados Pessoais
    public $name;
    public $cpf;
    public $birth_date;
    public $mother_name;
    public $phone;
    public $email;
    public $password;
    public $password_confirmation;
    
    // Endereço
    public $address;
    public $number;
    public $complement;
    public $neighborhood;
    public $city;
    public $state;
    public $zipcode;
    
    // Configurações
    public $credit_limit;
    public $billing_close_day = 30;
    public $status = 'pending';
    
    // Upload
    public $photo;
    public $currentPhoto;
    
    protected $rules = [
        'name' => 'required|string|max:255',
        'cpf' => 'required|cpf',
        'birth_date' => 'required|date|before:today',
        'mother_name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'email' => 'nullable|email',
        'password' => 'nullable|string|min:8|confirmed',
        
        'address' => 'required|string|max:255',
        'number' => 'required|string|max:10',
        'complement' => 'nullable|string|max:100',
        'neighborhood' => 'required|string|max:100',
        'city' => 'required|string|max:100',
        'state' => 'required|string|size:2',
        'zipcode' => 'required|string|max:10',
        
        'credit_limit' => 'nullable|numeric|min:0',
        'billing_close_day' => 'required|integer|min:1|max:28',
        'status' => 'required|in:pending,active,blocked,inactive',
        
        'photo' => 'nullable|image|max:2048',
    ];
    
    public function mount(?Filho $filho = null)
    {
        $this->isEditing = $filho && $filho->exists;
        
        if ($this->isEditing) {
            $this->filho = $filho;
            $this->fill($filho->toArray());
            $this->currentPhoto = $filho->photo_url;
            if ($filho->birth_date) {
                $this->birth_date = $filho->birth_date->format('Y-m-d');
                // Se for string, date('Y-m-d', strtotime($filho->birth_date));
            }
            $this->cpf = $filho->cpf_formatted;
            $this->name = $filho->fullname;
            $this->email = $filho->user->email;
            $this->address = $filho->address ?? '';
            $this->number = $filho->address_number ?? '';
            $this->zipcode = $filho->zip_code ?? '';
        } else {
            $this->credit_limit = config('admin.default_credit_limit', 1000);
        }
    }
    
    public function updatedZipcode()
    {
        $zipcode = preg_replace('/[^0-9]/', '', $this->zipcode);

        if (strlen($zipcode) === 8) {
            $this->searchAddress();
        }
    }
    
    public function searchAddress()
    {
        // Busca endereço via API ViaCEP
        $zipcode = preg_replace('/[^0-9]/', '', $this->zipcode);
        
        $response = Http::get("https://viacep.com.br/ws/{$zipcode}/json/");
        
        if ($response->successful() && !isset($response['erro'])) {
            $data = $response->json();
            
            $this->address = $data['logradouro'] ?? '';
            $this->neighborhood = $data['bairro'] ?? '';
            $this->city = $data['localidade'] ?? '';
            $this->state = $data['uf'] ?? '';
            $this->resetErrorBag(['zipcode', 'address', 'neighborhood', 'city', 'state']);
        }
    }
    
    public function save(FilhoService $service)
    {
        $this->validate();
        
        try {
            
            $dto = CreateFilhoDTO::fromArray($this->all());
            
            if ($this->isEditing) {
                $service->update($this->filho, $dto);
                $message = 'Filho atualizado com sucesso!';
            } else {
                $this->filho = $service->create($dto);
                $message = 'Filho cadastrado com sucesso!';
            }
            
            // Upload de foto se houver
            if ($this->photo) {
                $service->uploadPhoto($this->filho, $this->photo);
            }
            
            session()->flash('success', $message);
            
            return redirect()->route('admin.filhos.show', $this->filho);
            
        } catch (\Exception $e) {

            \Log::debug('Erro: '.$e->getMessage() );
            session()->flash('error', 'Erro ao salvar: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.admin.filhos.filho-form');
    }
}
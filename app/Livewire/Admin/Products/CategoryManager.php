<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;


class CategoryManager extends Component
{
    use WithPagination;
    
    // Filtros de Busca
    public string $search = '';
    public string $filterStatus = ''; // '' = todos, '1' = ativo, '0' = inativo

    // Controle do Modal
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?string $editingId = null;
    
    // Propriedades do Formulário
    public string $name = '';
    public string $description = '';
    public string $type = 'product';
    public ?string $parent_id = null;
    public string $icon = '';
    public string $color = '#3B82F6';
    public int $order = 0;
    public bool $is_active = true;

    // Resetar paginação quando filtrar
    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }

    protected function rules(){
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => ['required', Rule::in(['product', 'study_material'])],
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($this->isEditing && $value === $this->editingId) {
                        $fail('Uma categoria não pode ser subcategoria dela mesma.');
                    }
                },
            ],
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
        // Importante para garantir que o modal abra
        $this->dispatch('open-modal', 'category-modal'); 
    }

    public function openEditModal(string $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        
        $this->editingId = $category->id;
        $this->type = $category->type;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->parent_id = $category->parent_id;
        $this->icon = $category->icon ?? '';
        $this->color = $category->color ?? '#3B82F6';
        $this->order = $category->order;
        $this->is_active = (bool) $category->is_active;
        
        $this->isEditing = true;
        $this->showModal = true;
        $this->dispatch('open-modal', 'category-modal');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('close-modal', 'category-modal');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->type = 'product';
        $this->description = '';
        $this->parent_id = null;
        $this->icon = '';
        $this->color = '#3B82F6';
        $this->order = 0;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['slug'] = Str::slug($validated['name']);

        try {
           
            if ($this->isEditing) {
                $category = Category::findOrFail($this->editingId);
                
                if ($validated['parent_id'] === $category->id) {
                    $validated['parent_id'] = null;
                }

                if($validated['parent_id'] == '' || empty($validated['parent_id'])){
                    $validated['parent_id'] = !empty($validated['parent_id']) ? $validated['parent_id'] : null;
                }   
           
                $category->update($validated);
                $message = 'Categoria atualizada com sucesso!';
            } else {
                $validated['order'] = $validated['order'] ?: (Category::max('order') + 1);
                Category::create($validated);
                $message = 'Categoria criada com sucesso!';
            }


        } catch (\Exception $e) {

            \Log::debug('Erro Save Categoria: '.$e->getMessage());

            $message = 'Ocorreu alguma falha na criação/atualização da categoria!';

             $this->closeModal();
             $this->dispatch('flash', message: $message, type: 'error');
            return ;
        }

        $this->closeModal();
        
        // Dispara o evento 'flash' para o admin.js capturar e mostrar o Toast
        $this->dispatch('flash', message: $message, type: 'success');
    }

    public function delete(string $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        if ($category->products()->exists()) {
            $this->dispatch('flash', message: 'Não é possível remover categoria com produtos vinculados.', type: 'error');
            return;
        }


        if ($category->children()->exists()) {
            
            $this->dispatch('flash', message: 'Não é possível remover categoria com subcategorias.', type: 'error');
            return;
        }

        $category->delete();
        $this->dispatch('flash', message: 'Categoria removida com sucesso!', type: 'success');
    }

    public function toggleStatus(string $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->update(['is_active' => !$category->is_active]);
        
        $status = $category->is_active ? 'ativada' : 'desativada';
        // Feedback visual rápido sem recarregar tudo
        $this->dispatch('flash', message: "Categoria {$status} com sucesso!", type: 'success');
    }

    public function render()
    {
        $query = Category::query()
            ->with(['parent']) // Otimização: Eager Loading
            ->withCount(['products', 'studyMaterials']);

        // Aplicar Filtro de Busca
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Aplicar Filtro de Status
        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus == '1');
        }

        $categories = $query
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(12);

        // Busca categorias pai para o select do modal (exclui a própria se estiver editando)
        $parentCategories = Category::whereNull('parent_id')
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.products.category-manager', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
        ]);
    }
}
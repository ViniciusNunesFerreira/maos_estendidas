<?php
// app/Http/Livewire/Admin/Products/ProductsList.php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsList extends Component
{
    use WithPagination;
    
    public $search = '';
    public $category = '';
    public $location = ''; // 'loja' ou 'cantina'
    public $status = '';
    public $view = 'grid'; // 'grid' ou 'list'
    public $orderBy = 'name';
    public $orderDirection = 'asc';
    public $perPage = 12;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'location' => ['except' => ''],
        'view' => ['except' => 'grid'],
    ];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->orderBy === $field) {
            $this->orderDirection = $this->orderDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orderBy = $field;
            $this->orderDirection = 'asc';
        }
    }
    
    public function getProductsProperty()
    {
        return Product::query()
            ->with(['category'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%')
                      ->orWhere('barcode', 'like', '%' . $this->search . '%');
            })
            ->when($this->category, fn($q) => $q->where('category_id', $this->category))
            ->when($this->location, fn($q) => $q->where('location', $this->location))
            ->when($this->status, fn($q) => $q->where('active', $this->status === 'active'))
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate($this->perPage);
    }
    
    public function getCategoriesProperty()
    {
        return Category::active()->get();
    }
    
    public function render()
    {
        return view('livewire.admin.products.products-list', [
            'products' => $this->products,
            'categories' => $this->categories,
        ]);
    }
}
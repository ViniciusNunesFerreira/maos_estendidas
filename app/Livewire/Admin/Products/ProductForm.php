<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Rules\Barcode;
use Illuminate\Support\Facades\Storage;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;
    public bool $isEditing = false;

    // Form fields
    public string $name = '';
    public string $sku = '';
    public string $barcode = '';
    public string $description = '';
    public ?string $category_id = null;
    public $price = '';
    public $cost_price = '';
    public int $stock_quantity = 0;
    public int $min_stock_alert = 5;
    public string $type = 'ambos';
    public bool $is_active = true;
    public $image = null;
    public ?string $currentImageUrl = null;



    protected function rules()
    {
        return [
            'name' => 'required|min:3|max:255',
            'sku' => 'nullable|unique:products,sku,' . ($this->product->id ?? 'NULL'),
            'category_id' => 'required|exists:categories,id',
            'stock_quantity' => 'required|integer',
            'image' => 'nullable|image|max:2048',
        ];
    }
    

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->product = $product;
            $this->isEditing = true;
            $this->fill([
                'name' => $product->name,
                'sku' => $product->sku ?? '',
                'barcode' => $product->barcode ?? '',
                'description' => $product->description ?? '',
                'category_id' => $product->category_id,
                'price' => number_format((float)($product->price ?? 0), 2, ',', '.'),
                'cost_price' =>number_format((float)($product->cost_price ?? 0), 2, ',', '.'),
                'stock_quantity' => $product->stock_quantity,
                'min_stock_alert' => $product->min_stock_alert,
                'type' => $product->type,
                'is_active' => (bool) $product->is_active,
                'currentImageUrl' =>  $product->image_url,
            ]);
        }
    }

    public function updatedImage(): void
    {
        $this->validateOnly('image');
    }

    public function updatedBarcode($value)
    {
        $this->validateOnly('barcode', [
            'barcode' => ['nullable', 'string', new Barcode],
        ]);
    }

    public function generateSku(): void
    {
        if(empty($this->category_id)){
            $this->dispatch('flash', message: 'Antes de gerar SKU precisa selecionar uma categoria', type: 'error');
            return ;
        }

        if (empty($this->sku)) {
            $prefix = $this->category_id 
                ? Category::find($this->category_id)?->slug ?? 'PRD'
                : 'PRD';
            
            $this->sku = strtoupper(substr($prefix, 0, 3)) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }

    private function convertBrlToFloat($value)
    {
        if (empty($value)) return 0.0;
        $onlyNumbers = preg_replace('/\D/', '', $value);
        return (float) ($onlyNumbers / 100);
    }

    public function save(): void
    {

        // Converte usando a lógica de centavos
        $priceConvertido = $this->convertBrlToFloat($this->price);
        $this->price = $priceConvertido;

        $costPriceConvertido = $this->convertBrlToFloat($this->cost_price);
        $this->cost_price = $costPriceConvertido;

        if ($priceConvertido <= 0) {
            $this->addError('price', 'O preço de venda deve ser maior que zero.');
            return;
        }

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50',
            'barcode' => ['nullable', 'string', new Barcode],
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|uuid|exists:categories,id',
            'price' => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_alert' => 'required|integer|min:0',
            'type' => 'required|in:loja,cantina,ambos',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);
        
        try { 
            $validated['slug'] = Str::slug($validated['name']);

            $productService = app(ProductService::class);

            // Upload de imagem
            if ($this->image) {
            
                $filename = Str::slug($this->name) . '-' . time() . '.webp'; // Usar WebP 
                $path = 'products/' . $filename;

                $manager = new ImageManager(new Driver());

                $img = $manager->read($this->image->getRealPath())
                    ->cover(800, 800)
                    ->toWebp(80);  

                Storage::disk('public')->put($path, (string) $img);

                $validated['image_url'] = $path;

                if ($this->isEditing && $this->product->image_url) {
                    Storage::disk('public')->delete($this->product->image_url);
                }
            }

            if ($this->isEditing) {
                $this->product->update($validated);
                
                $this->dispatch('flash', message: 'Produto atualizado com sucesso!', type: 'success');
            } else {
                $validated['sku'] = $validated['sku'] ?: $productService->generateSku($validated['category_id'] ?? null);
                Product::create($validated);
                $this->dispatch('flash', message: 'Produto criado com sucesso!', type: 'success');
            }

            $this->redirect(route('admin.products.index'));

        }catch( \Exception $e){

            $this->dispatch('flash', message: 'Erro ao Salvar/Atualizar o produto', type: 'error');
            $this->addError('save', 'Erro ao salvar: ' . $e->getMessage());

        }
    }

    public function removeImage(): void
    {
        if ($this->product && $this->product->image_url) {
            \Storage::disk('public')->delete($this->product->image_url);
            $this->product->update(['image_url' => null]);
            $this->currentImageUrl = null;
        }
        $this->image = null;
    }

    public function render()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('livewire.admin.products.product-form', [
            'categories' => $categories,
        ]);
    }
}
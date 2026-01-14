<?php
// app/Http/Controllers/Admin/ProductController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}
    
    /**
     * Listagem de produtos
     */
    public function index(): View
    {
        $stats = $this->productService->getStatistics();

        return view('admin.products.index', ['stats' => $stats]);
    }
    
    /**
     * Form de cadastro
     */
    public function create(): View
    {
        $categories = Category::active()->get();
        
        return view('admin.products.create', [
            'categories' => $categories,
        ]);
    }
    
    /**
     * Salvar novo produto
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        try {
            $product = $this->productService->create($request->validated());
            
            // Upload de imagens se houver
            if ($request->hasFile('images')) {
                $this->productService->uploadImages($product, $request->file('images'));
            }
            
            return redirect()
                ->route('admin.products.show', $product)
                ->with('success', 'Produto cadastrado com sucesso!');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erro ao cadastrar produto: ' . $e->getMessage());
        }
    }
    
    /**
     * Detalhes do produto
     */
    public function show(Product $product): View
    {
        $product->load(['category', 'images', 'stockMovements']);
        
        $stats = $this->productService->getStats($product);
        
        return view('admin.products.show', [
            'product' => $product,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Form de edição
     */
    public function edit(Product $product): View
    {
        return view('admin.products.edit', compact('product'));
    }
    
    /**
     * Atualizar produto
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->productService->update($product, $request->validated());
            
            // Upload de novas imagens se houver
            if ($request->hasFile('images')) {
                $this->productService->uploadImages($product, $request->file('images'));
            }
            
            return redirect()
                ->route('admin.products.show', $product)
                ->with('success', 'Produto atualizado com sucesso!');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erro ao atualizar produto: ' . $e->getMessage());
        }
    }
    
    /**
     * Deletar produto (soft delete)
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->productService->delete($product);
            
            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produto removido com sucesso!');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Erro ao remover produto: ' . $e->getMessage());
        }
    }
    
    /**
     * Importar produtos via Excel
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);
        
        try {
            $imported = $this->productService->importFromExcel($request->file('file'));
            
            return back()
                ->with('success', "{$imported} produtos importados com sucesso!");
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Erro ao importar produtos: ' . $e->getMessage());
        }
    }
    
    /**
     * Gerar código de barras
     */
    public function generateBarcode(Product $product)
    {
        try {
            $barcode = $this->productService->generateBarcode($product);
            
            return response()->json([
                'success' => true,
                'barcode' => $barcode,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
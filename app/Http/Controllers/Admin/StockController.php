<?php
// app/Http/Controllers/Admin/StockController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}
    
    /**
     * Listagem de estoque
     */
    public function index(): View
    {
        return view('admin.stock.index');
    }
    
    /**
     * Form de entrada de estoque
     */
    public function entry(): View
    {
        return view('admin.stock.entry');
    }
    
    /**
     * Salvar entrada de estoque
     */
    public function storeEntry(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->stockService->entry($request->all());
            
            return redirect()
                ->route('admin.stock.index')
                ->with('success', 'Entrada de estoque registrada!');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erro ao registrar entrada: ' . $e->getMessage());
        }
    }
    
    /**
     * Form de saída de estoque
     */
    public function out(): View
    {
        return view('admin.stock.out');
    }
    
    /**
     * Salvar saída de estoque
     */
    public function storeOut(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|in:loss,damage,expired,adjustment',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->stockService->out($request->all());
            
            return redirect()
                ->route('admin.stock.index')
                ->with('success', 'Saída de estoque registrada!');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erro ao registrar saída: ' . $e->getMessage());
        }
    }
    
    /**
     * Inventário físico
     */
    public function inventory(): View
    {
        return view('admin.stock.inventory');
    }
}
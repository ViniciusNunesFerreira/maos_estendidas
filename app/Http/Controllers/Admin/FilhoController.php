<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFilhoRequest;
use App\Http\Requests\Admin\UpdateFilhoRequest;
use App\Models\Filho;
use App\Services\Filho\FilhoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FilhoController extends Controller
{
    public function __construct(
        private FilhoService $filhoService
    ) {}
    
    /**
     * Listagem de filhos
     */
    public function index(): View
    {
        return view('admin.filhos.index');
    }
    
    /**
     * Form de cadastro
     */
    public function create(): View
    {
        return view('admin.filhos.create');
    }
    
    
    
    /**
     * Detalhes do filho
     */
    public function show(Filho $filho): View
    {
        // Eager loading das relações necessárias
        $filho->load([
            'user',
            'invoices' => fn($q) => $q->latest()->take(10),
            'orders' => fn($q) => $q->latest()->take(10),
            'subscription',
        ]);
        
        // Estatísticas
        $stats = $this->filhoService->getStats($filho);
        
        return view('admin.filhos.show', [
            'filho' => $filho,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Form de edição
     */
    public function edit(Filho $filho): View
    {
        return view('admin.filhos.edit', [
            'filho' => $filho,
        ]);
    }
    
    /**
     * Atualizar filho
     */
   /* public function update(UpdateFilhoRequest $request, Filho $filho): RedirectResponse
    {
        try {
            $this->filhoService->update($filho, $request->validated());
            
            return redirect()
                ->route('admin.filhos.show', $filho)
                ->with('success', 'Filho atualizado com sucesso!');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erro ao atualizar filho: ' . $e->getMessage());
        }
    }*/
    
    /**
     * Deletar filho (soft delete)
     */
    public function destroy(Filho $filho): RedirectResponse
    {
        try {
            $this->filhoService->delete($filho);
            
            return redirect()
                ->route('admin.filhos.index')
                ->with('success', 'Filho removido com sucesso!');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Erro ao remover filho: ' . $e->getMessage());
        }
    }
    
    /**
     * Fila de aprovações
     */
    public function approvalQueue(): View
    {
        return view('admin.filhos.approval');
    }
    
    /**
     * Aprovar cadastro
     */
    public function approve(Filho $filho): RedirectResponse
    {
        try {
            $this->filhoService->approve($filho);
            
            return back()
                ->with('success', 'Cadastro aprovado! Filho notificado por SMS e Email.');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Erro ao aprovar cadastro: ' . $e->getMessage());
        }
    }
    
    /**
     * Recusar cadastro
     */
    public function reject(Filho $filho): RedirectResponse
    {
        try {
            $reason = request('reason');
            $this->filhoService->reject($filho, $reason);
            
            return back()
                ->with('success', 'Cadastro recusado. Filho será notificado.');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Erro ao recusar cadastro: ' . $e->getMessage());
        }
    }
    
    /**
     * Ajustar crédito manualmente
     */
    public function adjustCredit(Filho $filho): RedirectResponse
    {
        try {
            $amount = request('amount');
            $reason = request('reason');
            
            $this->filhoService->adjustCredit($filho, $amount, $reason);
            
            return back()
                ->with('success', 'Crédito ajustado com sucesso!');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Erro ao ajustar crédito: ' . $e->getMessage());
        }
    }
}

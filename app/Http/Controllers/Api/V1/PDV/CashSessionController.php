<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\CashMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashSessionController extends Controller
{
    // Verifica status atual
    public function status(Request $request)
    {
        $session = CashSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->with(['movements' => function($q) {
                $q->latest()->limit(5); // Últimas 5 movs para auditoria rápida
            }])
            ->first();

        if (!$session) {
            return response()->json(['status' => 'closed']);
        }

        // Calcula totais por método de pagamento em tempo real
        $summary = $session->movements()
            ->select('payment_method', DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return response()->json([
            'status' => 'open',
            'session' => $session,
            'summary' => $summary // Opcional: Front pode ocultar isso do operador comum
        ]);
        
    }

    // 1. Abertura de Caixa
    public function open(Request $request)
    {
        // Verifica se já existe caixa aberto
        if (CashSession::where('user_id', $request->user()->id)->where('status', 'open')->exists()) {
            throw ValidationException::withMessages(['message' => 'Você já possui um caixa aberto.']);
        }

        $request->validate(['opening_balance' => 'required|numeric|min:0']);

        return DB::transaction(function () use ($request) {
            $session = CashSession::create([
                'user_id' => $request->user()->id,
                'device_id' => $request->input('device_id'),
                'opened_at' => now(),
                'opening_balance' => $request->opening_balance,
                'status' => 'open'
            ]);

            // Registra movimento de abertura (Fundo de Troco)
            CashMovement::create([
                'cash_session_id' => $session->id,
                'user_id' => $request->user()->id,
                'type' => 'opening',
                'amount' => $request->opening_balance,
                'payment_method' => 'dinheiro',
                'description' => 'Abertura de Caixa - Fundo de Troco'
            ]);

            return response()->json(['message' => 'Caixa aberto com sucesso', 'session' => $session]);
        });
    }

    // 3. Sangria (Retirada) e Suprimento (Entrada)
    public function movement(Request $request)
    {
        $request->validate([
            'type' => 'required|in:bleed,supply',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255'
        ]);

        $session = CashSession::where('user_id', $request->user()->id)->where('status', 'open')->firstOrFail();

        // Se for Sangria, o valor é negativo matematicamente, mas entra positivo no banco? 
        // Vamos padronizar: Sangria grava NEGATIVO no banco para facilitar somas.
        $amount = $request->type === 'bleed' ? -abs($request->amount) : abs($request->amount);

        // Validação: Não pode sangrar mais do que tem em dinheiro
        if ($request->type === 'bleed') {
            $currentCash = $session->movements()->where('payment_method', 'dinheiro')->sum('amount');
            if ($currentCash < abs($amount)) {
                throw ValidationException::withMessages(['amount' => 'Saldo em dinheiro insuficiente para esta sangria.']);
            }
        }

        $movement = CashMovement::create([
            'cash_session_id' => $session->id,
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'amount' => $amount,
            'payment_method' => 'dinheiro', // Geralmente sangria/suprimento é numerário
            'description' => $request->description
        ]);

        return response()->json(['message' => 'Movimentação registrada', 'movement' => $movement]);
    }

    // 4. Fechamento Cego
    public function close(Request $request)
    {
        $request->validate([
            'counted_cash' => 'required|numeric|min:0',
            'counted_card' => 'numeric|min:0', // Opcional, dependendo da regra
            'counted_pix' => 'numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request) {
            $session = CashSession::where('user_id', auth()->id())
                ->where('status', 'open')
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Totais por Método
            $totals = $session->movements()
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method');

            // 2. Separação Financeira
            $expectedCash = $totals['dinheiro'] ?? 0; // Inclui suprimentos/sangrias se type='dinheiro'
            $expectedCard = ($totals['cartao_credito'] ?? 0) + ($totals['cartao_debito'] ?? 0);
            $expectedPix  = $totals['pix'] ?? 0;
            
            // VENDAS FILHO (Virtual)
            $virtualSales = $totals['credito_interno'] ?? 0; 

            // 3. O que o operador contou (Blind Close)
            $countedCash = $request->input('counted_cash', 0);
            $countedCard = $request->input('counted_card', 0); // Opcional contar comprovantes
            $countedPix  = $request->input('counted_pix', 0);

            // 4. Cálculo da Diferença (Quebra)
            // Apenas Dinheiro físico gera quebra imediata de caixa para o operador pagar
            $diffCash = $countedCash - $expectedCash; 
            
            // Diferença total (para auditoria)
            $totalDiff = ($countedCash + $countedCard + $countedPix) - ($expectedCash + $expectedCard + $expectedPix);


            $session->update([
                'closed_at' => now(),
                'status' => 'closed',
                'calculated_balance' => $expectedCash + $expectedCard + $expectedPix + $virtualSales, // Total Geral Contábil
                'counted_balance' => $countedCash + $countedCard + $countedPix + $virtualSales, // Assumimos virtual como correto
                'difference' => $totalDiff, 
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => 'Caixa fechado com sucesso',
                'data' => [
                    'difference' => $totalDiff,
                    'expected_cash' => $expectedCash,
                    'pix_total'     => $expectedPix,
                    'counted_cash'  => $countedCash,
                    'card_total'    => $expectedCard,
                    'sales_total' => $session->calculated_balance,
                    'virtual_sales' => $virtualSales, // Mostra quanto vendeu para Filhos
                    'cash_difference' => $diffCash // Mostra se sobrou/faltou dinheiro
                ]
            ]);
        });
    }
}
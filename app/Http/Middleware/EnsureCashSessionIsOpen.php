<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CashSession;

class EnsureCashSessionIsOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       // Verifica se existe sessão aberta para este usuário
        $hasSession = CashSession::where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();

        if (!$hasSession) {
             return response()->json([
                'success' => false,
                'message' => 'Caixa Fechado. É necessário abrir o caixa para realizar vendas.',
                'error_code' => 'CASH_CLOSED'
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\StoreSetting;
use Carbon\Carbon;

class CheckStoreStatus
{
    public function handle(Request $request, Closure $next)
    {
        // Busca a configuração (ID 1)
        $settings = StoreSetting::find(1);

        // Se não houver config ou is_enabled for false, bloqueia
        if (!$settings || !$settings->is_enabled) {
            return response()->json([
                'success' => false,
                'message' => $settings->maintenance_message ?? 'Loja temporariamente indisponível.',
                'error_code' => 'STORE_DISABLED'
            ], 403);
        }

       
        /*
        $now = Carbon::now('America/Sao_Paulo');
        $day = strtolower($now->format('D'));
        // ... lógica de conferir se $now está entre os horários do json
        */

        return $next($request);
    }
}
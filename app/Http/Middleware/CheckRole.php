<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {

        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (!auth()->user()->hasAnyRole($roles)) {
            abort(403, 'Você não tem permissão para acessar esta área.');
        }
        
        return $next($request);
    }
}
<?php
// app/Http/Middleware/CheckFilhoStatus.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckFilhoStatus
{
    public function handle(Request $request, Closure $next)
    {
        $filho = $request->user();

        if ($filho && $filho->status === 'blocked') {
            return response()->json([
                'message' => 'Sua conta está bloqueada devido a faturas em atraso.',
                'status' => 'blocked'
            ], 403);
        }

        return $next($request);
    }
}
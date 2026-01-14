<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    /**
     * Lista de roles permitidas no painel administrativo.
     */
    protected array $allowedRoles = [
        'admin',
        'manager',
        'operator',
        'financial',
        'stock'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 1. Verifica se usuário está ativo
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Sua conta está inativa ou aguardando aprovação.']);
        }
        
        // 2. Verifica Roles
        // Nota: Assumindo que seu Model User usa Spatie Permission ou método hasRole customizado
        if (!$user->hasRole($this->allowedRoles)) {
            // Se estiver logado mas for um usuário comum (ex: filho), faz logout e nega
            if ($user->hasRole('user') || $user->hasRole('child')) {
                Auth::logout();
                return redirect()->route('login')
                    ->withErrors(['email' => 'Acesso exclusivo para colaboradores.']);
            }
            
            abort(403, 'Acesso não autorizado para este perfil.');
        }
        
        return $next($request);
    }
}
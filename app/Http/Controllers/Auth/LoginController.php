<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use App\Models\AuditLog;

class LoginController extends Controller implements HasMiddleware
{
    /**
     * Onde redirecionar após o login bem-sucedido.
     */
    protected string $redirectTo = '/admin';

    /**
     * Define os middlewares para este controlador (Padrão Laravel 11).
     */
    public static function middleware(): array
    {
        return [
            // Aplica 'guest' em todos os métodos, exceto no logout
            new Middleware('guest', except: ['logout']),
        ];
    }

    /**
     * Exibe o formulário de login.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Processa a tentativa de autenticação.
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. Validação robusta
        $credentials = $request->validate([
            'email' => ['required', 'email', 'string'],
            'password' => ['required', 'string'],
        ]);

        // 2. Tentativa de autenticação com 'Remember Me'
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            return $this->authenticated($request, Auth::user());
        }

        // 3. Falha na autenticação (proteção contra enumeração de usuários)
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * Lógica pós-autenticação (Segurança e RBAC).
     */
    protected function authenticated(Request $request, $user): RedirectResponse
    {
        // 1. Verificar se usuário está ativo
        if (isset($user->is_active) && !$user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Sua conta está inativa. Entre em contato com o administrador.'],
            ]);
        }

        // 2. Verificar se possui Role Administrativa
        $allowedRoles = ['admin', 'manager', 'operator', 'financial', 'stock'];
        
        if (method_exists($user, 'hasRole')) {
            if (!$user->hasRole($allowedRoles)) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => ['Acesso negado: Este painel é restrito a colaboradores autorizados.'],
                ]);
            }
        }

        // 3. Auditoria (Log de Sucesso)
        if (method_exists($user, 'auditLogs')) {
            
            AuditLog::create([
                'user_id' => $user->id,
                'auditable_type' => get_class($user),
                'auditable_id' => $user->id,
                'action' => 'login',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

        }

        return redirect()->intended($this->redirectTo);
    }

    /**
     * Finaliza a sessão do usuário.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        
        if (method_exists($user, 'auditLogs')) {
            // Registra o log usando sua model AuditLog que já possui HasUuid
            AuditLog::create([
                'user_id'        => $user->id,
                'auditable_type' => get_class($user),
                'auditable_id'   => $user->id,
                'action'         => 'logout',
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'url'            => $request->fullUrl(),
                'method'         => $request->method(),
                'old_values'     => [], // Opcional, conforme seu cast no AuditLog.php
                'new_values'     => [], 
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Método auxiliar para retornar o caminho de redirecionamento.
     */
    protected function redirectPath(): string
    {
        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }
}
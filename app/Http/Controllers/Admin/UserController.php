<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Listar usuários do sistema
     */
    public function index(): View
    {
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'managers' => User::where('role', 'manager')->count(),
            'operators' => User::where('role', 'operator')->count(),
            'active' => User::where('is_active', true)->count(),
        ];

        return view('admin.users.index', compact('stats'));
    }

    /**
     * Formulário de criação
     */
    public function create(): View
    {
        $roles = $this->getAvailableRoles();
        
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Salvar novo usuário
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|in:admin,manager,operator',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        User::create($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuário criado com sucesso!');
    }

    /**
     * Exibir usuário
     */
    public function show(User $user): View
    {
        // Carregar últimas atividades
        $recentActivity = $user->auditLogs()
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.users.show', compact('user', 'recentActivity'));
    }

    /**
     * Formulário de edição
     */
    public function edit(User $user): View
    {
        $roles = $this->getAvailableRoles();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Atualizar usuário
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,manager,operator',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Não permitir que o usuário remova seu próprio acesso admin
        if ($user->id === auth()->id() && $user->role === 'admin' && $validated['role'] !== 'admin') {
            return redirect()
                ->back()
                ->with('error', 'Você não pode remover seu próprio papel de administrador.');
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Alterar senha do usuário
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Senha alterada com sucesso!');
    }

    /**
     * Ativar/Desativar usuário
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Não permitir desativar a si mesmo
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'Você não pode desativar sua própria conta.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'ativado' : 'desativado';

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuário {$status} com sucesso!");
    }

    /**
     * Remover usuário
     */
    public function destroy(User $user): RedirectResponse
    {
        // Não permitir remover a si mesmo
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'Você não pode remover sua própria conta.');
        }

        // Verificar se é filho (tem relacionamento)
        if ($user->filho) {
            return redirect()
                ->back()
                ->with('error', 'Este usuário está vinculado a um filho. Remova o filho primeiro.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuário removido com sucesso!');
    }

    /**
     * Obter papéis disponíveis
     */
    private function getAvailableRoles(): array
    {
        return [
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'operator' => 'Operador',
        ];
    }
}
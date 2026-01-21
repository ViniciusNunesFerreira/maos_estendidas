<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\Filho;
use App\Models\User;
use App\Models\PasswordResetSms;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\AccountNotActiveException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Filho\FilhoService;

class AuthService
{
    public function __construct(
        private SmsService $smsService,
        private FilhoService $filhoService,
    ) {}

    /**
     * Registro de novo Filho utilizando a estrutura do FilhoService
     * Regra: Status inicial 'inactive' para aprovação administrativa.
     */
    public function register(array $data): array
    {
        // Higienização inicial de dados sensíveis
        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        
        // Garantir que o status inicial seja 'inactive' independente do que vier do PWA
        $data['status'] = 'inactive';
        
        // Se não houver email, gera um padrão baseado no CPF para evitar quebra de integridade no model User
        if (empty($data['email'])) {
            $data['email'] = $data['cpf'] . '@maosestendidas.local';
        }

        // Delegamos para o FilhoService.create que já possui o DB::transaction
        // e gerencia a criação simultânea de User e Filho com todos os campos de endereço.
        $filho = $this->filhoService->create($data);

        return [
            'user' => $filho->user,
            'filho' => $filho
        ];
    }


    /**
     * Login via CPF + Senha (App/Totem)
     */
    public function loginByCpf(string $cpf, string $password, string $deviceInfo = null): array
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
       // Buscamos o Filho pelo CPF carregando a relação de usuário
        $filho = Filho::where('cpf', $cpf)->with(['user', 'subscription'])->first();
        
        if (!$filho || !$filho->user) {
            throw new InvalidCredentialsException('CPF ou senha inválidos');
        }
        
        if (!Hash::check($password, $filho->user->password)) {
            throw new InvalidCredentialsException('CPF ou senha inválidos');
        }
        
        // Verificação de Status conforme requisitos do PWA
        if ($filho->status === 'inactive') {
            throw new AccountNotActiveException(
                "Sua conta está aguardando aprovação do administrador.",
                'ACCOUNT_PENDING_APPROVAL'
            );
        }

        if ($filho->status === 'blocked') {
            throw new AccountNotActiveException(
                "Acesso suspenso. Entre em contato com a administração.",
                'ACCOUNT_BLOCKED'
            );
        }
        
                
        // Criar token
        $tokenName = $deviceInfo ?? 'app-token';
        $token = $filho->user->createToken($tokenName, ['filho:*'])->plainTextToken;
        
        return [
            'token' => $token,
            'user' => [
                'id' => $filho->user->id,
                'name' => $filho->user->name,
                'email' => $filho->user->email,
            ],
            'filho' => [
                'id' => $filho->id,
                'cpf' => $filho->cpf_formatted,
                'birth_date' => $filho->birth_date,
                'mother_name' => $filho->mother_name,
                'phone' => $filho->phone_formatted,
                'status' => $filho->status,
                'is_blocked_by_debt' => (bool) $filho->is_blocked_by_debt,
                'credit_limit' => (float) $filho->credit_limit,
                'credit_used' => (float) $filho->credit_used,
                'is_suspended' => (bool) $filho->is_blocked_by_debt,
                'credit_available' => (float) ($filho->credit_limit - $filho->credit_used),
                'subscription' => $filho->subscription()->select('plan_name', 'amount')->first()
                
            ],
        ];
    }

    /**
     * Solicitar reset de senha via SMS
     */
    public function requestPasswordResetSms(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        $filho = Filho::byCpf($cpf)->first();
        
        if (!$filho || !$filho->phone) {
            // Não revelar se o CPF existe
            return true;
        }
        
        // Gerar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Criar registro
        PasswordResetSms::create([
            'phone' => $filho->phone,
            'cpf' => $cpf,
            'code' => $code,
            'expires_at' => now()->addMinutes(config('casalar.sms.reset_code_expiry', 15)),
            'ip_address' => request()->ip(),
        ]);
        
        // Enviar SMS
        $this->smsService->send(
            $filho->phone,
            "Casa Lar: Seu código de recuperação é {$code}. Válido por 15 minutos."
        );
        
        return true;
    }

    /**
     * Verificar código SMS
     */
    public function verifyResetCode(string $cpf, string $code): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        $reset = PasswordResetSms::where('cpf', $cpf)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 3)
            ->first();
        
        if (!$reset) {
            // Incrementar tentativas se existir registro
            PasswordResetSms::where('cpf', $cpf)
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->increment('attempts');
            
            return false;
        }
        
        return true;
    }

    /**
     * Resetar senha com código SMS
     */
    public function resetPasswordWithSms(string $cpf, string $code, string $newPassword): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        $reset = PasswordResetSms::where('cpf', $cpf)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$reset) {
            return false;
        }
        
        $filho = Filho::byCpf($cpf)->first();
        
        if (!$filho) {
            return false;
        }
        
        // Atualizar senha
        $filho->user->update([
            'password' => Hash::make($newPassword),
        ]);
        
        // Marcar código como usado
        $reset->update([
            'used' => true,
            'used_at' => now(),
        ]);
        
        // Revogar tokens existentes
        $filho->user->tokens()->delete();
        
        return true;
    }
}
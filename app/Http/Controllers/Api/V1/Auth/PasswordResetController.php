<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Filho;
use App\Notifications\Auth\ResetPasswordOtpNotification;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Passo 1: Solicitar Código
     */
    public function requestSms(Request $request)
    {
        $request->validate(['cpf' => 'required|string']);
        
        // Limpa CPF
        $cpf = preg_replace('/\D/', '', $request->cpf);

        // Busca usuário (Ajuste a Model conforme sua estrutura de BD)
        $filho = Filho::where('cpf', $cpf)->first();

        if (!$filho) {
            // Retorna sucesso fake por segurança (Filho Enumeration) ou erro amigável dependendo da política
            return response()->json([
                'success' => false,
                'message' => 'CPF não encontrado em nossa base.'
            ], 404);
        }

        // Gera código de 6 dígitos
        $code = (string) rand(100000, 999999);

        // Armazena/Atualiza código
        DB::table('password_reset_codes')->updateOrInsert(
            ['cpf' => $cpf],
            [
                'code' => $code,
                'token' => null, // Reseta token anterior
                'expires_at' => Carbon::now()->addMinutes(10),
                'verified_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        );

        // Envia notificação
        try {
            $filho->notify(new ResetPasswordOtpNotification($code));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao enviar SMS/WhatsApp.'], 500);
        }

        // Mascara o telefone para retorno
        $phone = $filho->phone;
        $maskedPhone = substr($phone, 0, 2) . ' (' . substr($phone, 2, 2) . ') *****-' . substr($phone, -4);

        return response()->json([
            'success' => true,
            'message' => 'Código enviado.',
            'data' => ['masked_phone' => $maskedPhone]
        ]);
    }

    /**
     * Passo 2: Validar Código
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'cpf' => 'required',
            'code' => 'required|string|size:6'
        ]);

        $cpf = preg_replace('/\D/', '', $request->cpf);

        $record = DB::table('password_reset_codes')
            ->where('cpf', $cpf)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido ou expirado.'
            ], 400);
        }

        // Gera um token assinado para permitir a troca de senha
        $token = Str::random(60);
        
        DB::table('password_reset_codes')
            ->where('id', $record->id)
            ->update([
                'verified_at' => Carbon::now(),
                'token' => $token
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Código validado.',
            'data' => ['reset_token' => $token]
        ]);
    }

    /**
     * Passo 3: Redefinir Senha
     */
    public function resetWithSms(Request $request)
    {
        $request->validate([
            'cpf' => 'required',
            'token' => 'required',
            'password' => 'required|min:6|confirmed' // espera password_confirmation
        ]);

        $cpf = preg_replace('/\D/', '', $request->cpf);

        // Verifica se existe uma validação prévia com este token
        $record = DB::table('password_reset_codes')
            ->where('cpf', $cpf)
            ->where('token', $request->token)
            ->whereNotNull('verified_at')
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Sessão de redefinição inválida. Recomece o processo.'
            ], 400);
        }

        // Atualiza a senha
        $filho = filho::with('user')->where('cpf', $cpf)->first();
        $user = $filho->user;
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // Limpa o código usado
        DB::table('password_reset_codes')->where('cpf', $cpf)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Senha alterada com sucesso.'
        ]);
    }
}
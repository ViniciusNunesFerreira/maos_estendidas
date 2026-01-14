<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\UpdateProfileRequest;
use App\Http\Requests\App\UpdatePasswordRequest;
use App\Http\Resources\FilhoResource;
use App\Services\FilhoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        private readonly FilhoService $filhoService
    ) {}

    /**
     * Exibir perfil do filho autenticado
     * 
     * GET /api/v1/app/profile
     */
    public function show(): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil não encontrado',
            ], 404);
        }

        $filho->load(['user', 'subscription']);

        return response()->json([
            'success' => true,
            'data' => FilhoResource::collection($filho),
        ]);
    }

    /**
     * Atualizar perfil do filho
     * 
     * PUT /api/v1/app/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $filho = $request->user()->filho;

            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil não encontrado',
                ], 404);
            }

            $data = $request->validated();

            // Atualizar dados do filho
            $filho->update([
                'name' => $data['name'] ?? $filho->name,
                'phone' => $data['phone'] ?? $filho->phone,
                'birth_date' => $data['birth_date'] ?? $filho->birth_date,
                'mother_name' => $data['mother_name'] ?? $filho->mother_name,
            ]);

            // Atualizar endereço se fornecido
            if (isset($data['street'])) {
                $filho->update([
                    'street' => $data['street'],
                    'number' => $data['number'],
                    'complement' => $data['complement'] ?? null,
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'zipcode' => $data['zipcode'],
                ]);
            }

            // Atualizar email do usuário se fornecido
            if (isset($data['email'])) {
                $filho->user->update(['email' => $data['email']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso',
                'data' => new FilhoResource($filho->fresh(['user', 'subscription'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar senha do filho
     * 
     * PUT /api/v1/app/profile/password
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Verificar senha atual
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha atual incorreta',
                    'errors' => [
                        'current_password' => ['A senha atual está incorreta'],
                    ],
                ], 422);
            }

            // Atualizar senha
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Revogar tokens antigos (exceto o atual)
            $currentToken = $user->currentAccessToken();
            $user->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Senha atualizada com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar senha: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload de foto do perfil
     * 
     * POST /api/v1/app/profile/photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $filho = $request->user()->filho;

            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil não encontrado',
                ], 404);
            }

            // Deletar foto antiga se existir
            if ($filho->photo_url) {
                Storage::disk('public')->delete($filho->photo_url);
            }

            // Salvar nova foto
            $path = $request->file('photo')->store('filhos/photos', 'public');

            $filho->update(['photo_url' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Foto atualizada com sucesso',
                'data' => [
                    'photo_url' => Storage::disk('public')->url($path),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload da foto: ' . $e->getMessage(),
            ], 500);
        }
    }
}
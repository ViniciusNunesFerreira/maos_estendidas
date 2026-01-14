<?php
// app/Services/FilhoService.php

namespace App\Services\Filho;

use App\Models\Filho;
use App\Models\User;
use App\DTOs\CreateFilhoDTO;
use App\DTOs\UpdateFilhoDTO;
use App\Services\SubscriptionService;
use App\Jobs\SendFilhoApprovalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FilhoService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Criar novo filho (cadastro inicial - status pending)
     */
    public function create(CreateFilhoDTO $dto): Filho
    {
        return DB::transaction(function () use ($dto) {

            // Criar usuário
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email ?? "{$dto->cpf}@maosestendidas.local", // Email opcional
                'password' => Hash::make($dto->password),
                'phone' => preg_replace('/\D/', '',  $dto->phone),
                'role' => 'filho',
                'is_active' => false, // Ativo após aprovação
            ]);
            
            // Criar filho
            $filho = Filho::create([
                'user_id' => $user->id,
                'cpf' => preg_replace('/\D/', '', $dto->cpf),
                'birth_date' => $dto->birthDate,
                'mother_name' => $dto->motherName, // NOVO CAMPO
                'phone' => preg_replace('/\D/', '',  $dto->phone),
                'address' => $dto->address,
                'address_number' => $dto->addressNumber,
                'address_complement' => $dto->addressComplement,
                'neighborhood' => $dto->neighborhood,
                'city' => $dto->city,
                'state' => $dto->state,
                'zip_code' => preg_replace('/\D/', '', $dto->zipCode),
                'credit_limit' => $dto->creditLimit ?? config('casalar.credit.default_limit', 10000),
                'billing_close_day' => config('casalar.billing.default_close_day', 30),
                'max_overdue_invoices' => config('casalar.credit.max_overdue_invoices', 3),
                'status' => 'inactive',
                'admission_date' => now(),
            ]);
            
            return $filho;
        });
    }

    /**
     * Aprovar cadastro do filho
     */
    public function approve(
        Filho $filho, 
        //Valor momentaneo Fixo para posterior colocar por edição nas configurações
        float $subscriptionAmount = 120,
    ): Filho {
        return DB::transaction(function () use ($filho, $subscriptionAmount) {
            // Atualizar status
            $filho->update([
                'status' => 'active',
            ]);
            
            // Criar assinatura
            $this->subscriptionService->createForFilho(
                $filho, 
                $subscriptionAmount,
            );
            
            // Enviar notificação 
           // SendFilhoApprovalNotification::dispatch($filho);
            
            return $filho->fresh(['subscription', 'user']);
        });
    }

    /**
     * Suspender filho
     */
    public function suspend(Filho $filho, string $reason = null, $is_blocked_by_debt = false): Filho
    {
        return DB::transaction(function () use ($filho, $reason, $is_blocked_by_debt) {

            \Log::debug('Filho: '.$filho );
            \Log::info($reason);

            try{
                
                $filho->update([
                    'status' => 'suspended',
                    'notes' => $reason,
                    'is_blocked_by_debt' => $is_blocked_by_debt,
                    'block_reason' => $is_blocked_by_debt ? 'Limite de faturas vencidas atingido': null,
                    'blocked_at' => now(),
                ]);
            
            
                // Pausar assinatura
                if ($filho->subscription) {
                    $this->subscriptionService->pause($filho->subscription, $reason);
                }

            }catch(\Exception $e){
                \Log::debug('Error: '.$e->getMessage());
            }
            
            return $filho->fresh();
        });
    }

    /**
     * Reativar filho
     */
    public function reactivate(Filho $filho): Filho
    {
        return DB::transaction(function () use ($filho) {
            $filho->update([
                'status' => 'active',
                'notes' => null, 
                'is_blocked_by_debt' => false,
                'block_reason' => null,
                'blocked_at' => null,
            ]);
            
            // Reativar assinatura
            if ($filho->subscription && $filho->subscription->status === 'paused') {
                $this->subscriptionService->reactivate($filho->subscription);
            }
            
            return $filho->fresh();
        });
    }

    /**
     * Buscar filho por CPF (para login)
     */
    public function findByCpf(string $cpf): ?Filho
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        return Filho::byCpf($cpf)->with('user')->first();
    }

    /**
     * Atualizar dados do filho
     */
    public function update(Filho $filho, CreateFilhoDTO $dto): Filho
    {
        $updateData = array_filter([
            'birth_date' => $dto->birthDate,
            'mother_name' => $dto->motherName,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'address_number' => $dto->addressNumber,
            'address_complement' => $dto->addressComplement,
            'neighborhood' => $dto->neighborhood,
            'city' => $dto->city,
            'state' => $dto->state,
            'zip_code' => $dto->zipCode,
            'emergency_contact' => $dto->emergencyContact,
            'emergency_phone' => $dto->emergencyPhone,
            'credit_limit' => $dto->creditLimit,
            'billing_close_day' => $dto->billingCloseDay,
        ], fn($value) => $value !== null);
        
        $filho->update($updateData);
        
        // Atualizar nome no user
        if ($dto->name) {
            $filho->user->update(['name' => $dto->name]);
        }

        if ($dto->email) {
            $filho->user->update(['email' => $dto->email]);
        }
        
        return $filho->fresh(['user']);
    }

    /**
     * Ajustar limite de crédito
     */
    public function adjustCreditLimit(Filho $filho, float $newLimit): Filho
    {
        $filho->update(['credit_limit' => $newLimit]);
        return $filho->fresh();
    }


    /**
     * Obter estatísticas
     */
    public function getStatistics(): array
    {
        return [
            'total' => Filho::count(),
            'active' => Filho::where('status', 'active')->count(),
            'pending' => Filho::where('status', 'pending')->count(),
            'rejected' => Filho::where('status', 'rejected')->count(),
            'inactive' => Filho::where('status', 'inactive')->count(),
            'blocked' => Filho::where('is_blocked_by_debt', true)->count(),
            'with_overdue_invoices' => Filho::whereHas('invoices', fn($q) => $q->where('status', 'overdue'))->count(),
        ];
    }


    /**
     * Retorna estatísticas específicas de um Filho para o perfil (show)
     */
    public function getStats(Filho $filho): array
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // 1. Calcular Consumo do Mês Atual (Transações de débito)
        $currentMonthConsumption = $filho->transactions()
            ->where('type', 'debit')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount');

        // 2. Calcular Consumo do Mês Anterior
        $lastMonthConsumption = $filho->transactions()
            ->where('type', 'debit')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('amount');

        // 3. Calcular Porcentagem de Crescimento/Queda
        $consumptionPercentage = 0;
        $trend = 'equal';

        if ($lastMonthConsumption > 0) {
            $diff = $currentMonthConsumption - $lastMonthConsumption;
            $consumptionPercentage = abs(round(($diff / $lastMonthConsumption) * 100));
            $trend = $currentMonthConsumption >= $lastMonthConsumption ? 'up' : 'down';
        } elseif ($currentMonthConsumption > 0) {
            // Se mês passado foi 0 e este mês tem gasto, é 100% de aumento (ou tratado como "up")
            $consumptionPercentage = 100;
            $trend = 'up';
        }

        // Retorna o array exatamente como a view espera
        return [
            'monthly_consumption' => $currentMonthConsumption,
            'consumption_trend' => $trend, // 'up' ou 'down'
            'consumption_percentage' => $consumptionPercentage,
            
            // Dados extras que sua view usa nos outros cards
            'credit_limit' => $filho->credit_limit,
            'credit_used' => $filho->credit_used,
            'credit_available' => $filho->credit_available, // Via Accessor do Model
            
            // Contagem de faturas (usando os Accessors ou Relations do Model Filho)
            'pending_invoices_count' => $filho->invoices()->where('status', 'pending')->count(),
            'overdue_invoices_count' => $filho->total_overdue_invoices, // Accessor criado no Filho.php
        ];
    }
}
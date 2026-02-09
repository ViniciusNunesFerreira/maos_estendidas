<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;

class Filho extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use Auditable;
    use Notifiable;
    

    protected $table = 'filhos';

    protected $fillable = [
        'user_id',
        'cpf',
        'birth_date',
        'mother_name',        
        'phone',
        'address',
        'address_number',
        'address_complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'status',
        'notes',
        'admission_date',
        'departure_date',
        'credit_limit',       // NOVO: Limite de crédito
        'credit_used',        // NOVO: Crédito utilizado do mês // Precisa zerar sempre que 
        'billing_close_day',  // NOVO: Dia fechamento fatura
        'max_overdue_invoices', // NOVO: Máx faturas vencidas
        'is_blocked_by_debt', // NOVO
        'block_reason',       // NOVO
        'blocked_at',         // NOVO
    ];

    protected $casts = [
        'birth_date' => 'date',
        'admission_date' => 'date',
        'departure_date' => 'date',
        'blocked_at' => 'datetime',
        'credit_limit' => 'decimal:2',
        'credit_used' => 'decimal:2',
        'billing_close_day' => 'integer',
        'max_overdue_invoices' => 'integer',
        'is_blocked_by_debt' => 'boolean',
    ];

    protected $attributes = [
        'credit_limit' => 10000.00,
        'credit_used' => 0.00,
        'billing_close_day' => 30,
        'max_overdue_invoices' => 3,
        'is_blocked_by_debt' => false,
        'status' => 'inactive',
    ];

    protected $appends = ['age', 'is_mensalidade_due', 'status_label', 'status_color', 'photo_url', 'credit_available'];

    // Relacionamentos
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latest('created_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }


    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }


    // Accessors

     /**
     * Crédito disponível (limite - usado)
     */
    public function getCreditAvailableAttribute(): float
    {
        return max(0, $this->credit_limit - $this->credit_used);
    }

    /**
     * Percentual de crédito utilizado
     */
    public function getCreditUsagePercentAttribute(): float
    {
        if ($this->credit_limit <= 0) {
            return 100;
        }
        return round(($this->credit_used / $this->credit_limit) * 100, 2);
    }

        /**
     * Quantidade de faturas de consumo vencidas
     */
    public function getOverdueInvoicesCountAttribute(): int
    {
        return $this->invoices()
            ->where('status', 'overdue')
            ->count();
    }

    /**
     * Quantidade de faturas de assinatura vencidas
     */
    public function getOverdueSubscriptionInvoicesCountAttribute(): int
    {
        return $this->invoices()
            ->where('type', 'subscription')
            ->where('status', 'overdue')
            ->count();
    }

    /**
     * Total de faturas vencidas (consumo + assinatura)
     */
    public function getTotalOverdueInvoicesAttribute(): int
    {
        return $this->overdue_invoices_count + $this->overdue_subscription_invoices_count;
    }

    /**
     * Se pode fazer compras (baseado em faturas vencidas)
     */
    public function getCanPurchaseAttribute(): bool
    {
        if ($this->is_blocked_by_debt) {
            return false;
        }
        
        if ($this->status !== 'active') {
            return false;
        }
        
        return $this->total_overdue_invoices < $this->max_overdue_invoices;
    }

    /**
     * Nome completo formatado
     */
    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? '';
    }

    public function getPhotoUrlAttribute(): string 
    {
        $path = $this->user?->avatar_url;
        if (!$path) {
            return '';
        }
        // Se o path já for uma URL completa (ex: S3 ou link externo)
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return asset($path);
    }

     /**
     * CPF formatado
     */
    public function getCpfFormattedAttribute(): string
    {
        $cpf = preg_replace('/\D/', '', $this->cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    /**
     * Telefone formatado
     */
    public function getPhoneFormattedAttribute(): string
    {
        $phone = preg_replace('/\D/', '', $this->phone);
        if (strlen($phone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        }
        return $phone;
    }

    /**
     * Acesssor para formatar o CEP (00000-000)
     */
    public function getZipCodeFormattedAttribute(): string
    {
        // Remove qualquer caractere que não seja número
        $zip = preg_replace('/\D/', '', $this->zip_code);

        // Verifica se possui os 8 dígitos padrão do Brasil
        if (strlen($zip) === 8) {
            return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $zip);
        }

        return $zip;
    }


    public function getAgeAttribute()
    {
        $dataAtual = new \DateTime();
        $date2 = new \DateTime($this->birth_date); 
        $age =  $dataAtual->diff($date2);
        return $age->y;
    }

    


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked_by_debt', true);
    }

    public function scopeCanPurchase($query)
    {
        return $query->where('status', 'active')
                     ->where('is_blocked_by_debt', false);
    }

    public function scopeByCpf($query, string $cpf)
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        return $query->where('cpf', $cpf);
    }



    // Métodos de negócio

     /**
     * Usar crédito (para compras)
     */
    public function useCredit(float $amount): bool
    {
        if ($amount > $this->credit_available) {
            return false;
        }

        $this->increment('credit_used', $amount);
        return true;
    }

    /**
     * Restaurar crédito (quando fatura é paga)
     */
    public function restoreCredit(float $amount): void
    {
        $this->decrement('credit_used', min($amount, $this->credit_used));
    }

    /**
     * Bloquear por inadimplência
     */
    public function blockByDebt(string $reason = null): void
    {
        $this->update([
            'is_blocked_by_debt' => true,
            'block_reason' => $reason ?? 'Limite de faturas vencidas atingido',
            'blocked_at' => now(),
        ]);
    }

    /**
     * Desbloquear
     */
    public function unblock(): void
    {
        $this->update([
            'is_blocked_by_debt' => false,
            'block_reason' => null,
            'blocked_at' => null,
        ]);
    }

    /**
     * Verificar e atualizar status de bloqueio
     */
    public function checkAndUpdateBlockStatus(): void
    {
        $shouldBeBlocked = $this->total_overdue_invoices >= $this->max_overdue_invoices;

        if ($shouldBeBlocked && !$this->is_blocked_by_debt) {
            $this->blockByDebt();
        } elseif (!$shouldBeBlocked && $this->is_blocked_by_debt) {
            $this->unblock();
        }
    }




    public function credit(float $amount, string $description, ?string $type = 'credit'): Transaction
    {
        $transaction = $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $this->balance,
            'balance_after' => $this->balance + $amount,
            'description' => $description,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->increment('balance', $amount);

        return $transaction;
    }

    public function debit(float $amount, string $description, ?string $type = 'debit'): Transaction
    {
        if (!$this->hasBalance($amount)) {
            throw new \App\Exceptions\InsufficientBalanceException(
                "Saldo insuficiente. Saldo atual: R$ {$this->balance}"
            );
        }

        $transaction = $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $this->balance,
            'balance_after' => $this->balance - $amount,
            'description' => $description,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->decrement('balance', $amount);

        return $transaction;
    }

    public function isWithinDailyLimit(float $amount): bool
    {
        if (!$this->daily_limit) {
            return true;
        }

        $todaySpent = $this->transactions()
            ->whereDate('created_at', today())
            ->where('type', 'debit')
            ->sum('amount');

        return ($todaySpent + $amount) <= $this->daily_limit;
    }

    public function isWithinMonthlyLimit(float $amount): bool
    {
        if (!$this->monthly_limit) {
            return true;
        }

        $monthSpent = $this->transactions()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('type', 'debit')
            ->sum('amount');

        return ($monthSpent + $amount) <= $this->monthly_limit;
    }


    public function getIsMensalidadeDueAttribute(): bool
    {
        //Todo vencimento será dia 05
        $today = now();
        $dueDate = $today->copy()->day(5);

        if ($today->day > 5) {
            $dueDate->addMonth();
        }

        return $today->isSameDay($dueDate) || $today->isAfter($dueDate);
    }


    public function getStatusLabelAttribute(): string
    {
        // Prioridade 1: Bloqueio por inadimplência
        if ($this->is_blocked_by_debt) {
            return 'Bloqueado (Débito)';
        }

        // Prioridade 2: Mapeamento do campo status
        return match ($this->status) {
            'active'  => 'Ativo',
            'suspend' => 'Bloqueado',
            'inactive' => 'Inativo',
            default    => ucfirst($this->status),
        };
    }

    /**
     * Retorna a cor do badge baseada no status
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_blocked_by_debt) {
            return 'danger'; // Vermelho
        }

        return match ($this->status) {
            'active'  => 'success', // Verde
            'blocked' => 'danger', 
            'inactive' => 'danger', // Vermelho
            default    => 'gray',    // Cinza
        };
    }




}
<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetSms extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'password_reset_sms';

    protected $fillable = [
        'phone',
        'cpf',
        'code',
        'expires_at',
        'attempts',
        'max_attempts',
        'used',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    protected $attributes = [
        'attempts' => 0,
        'max_attempts' => 3,
        'used' => false,
    ];

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeValid($query)
    {
        return $query->where('used', false)
                     ->where('expires_at', '>', now())
                     ->whereColumn('attempts', '<', 'max_attempts');
    }

    public function scopeForCpf($query, string $cpf)
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        return $query->where('cpf', $cpf);
    }

    public function scopeForPhone($query, string $phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        return $query->where('phone', $phone);
    }

    public function scopeRecent($query, int $minutes = 15)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // =========================================================
    // MÉTODOS
    // =========================================================

    public function isValid(): bool
    {
        return !$this->used 
            && $this->expires_at > now() 
            && $this->attempts < $this->max_attempts;
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    public function hasMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function markAsUsed(): void
    {
        $this->update([
            'used' => true,
            'used_at' => now(),
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function createForCpf(string $cpf, string $phone): self
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        $phone = preg_replace('/\D/', '', $phone);
        
        return static::create([
            'phone' => $phone,
            'cpf' => $cpf,
            'code' => static::generateCode(),
            'expires_at' => now()->addMinutes(config('casalar.sms.reset_code_expiry', 15)),
            'ip_address' => request()->ip(),
        ]);
    }
}
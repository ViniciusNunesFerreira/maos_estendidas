<?php
// app/Models/User.php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasApiTokens;
    use Notifiable;
    use HasUuid;
    use SoftDeletes;
    use Auditable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'avatar_url',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Relacionamentos
    public function filho(): HasOne
    {
        return $this->hasOne(Filho::class);
    }

    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by_user_id');
    }

    public function approvedComprasExternas(): HasMany
    {
        return $this->hasMany(CompraExterna::class, 'approved_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Métodos auxiliares
    public function isAdmin(): bool
    {
       // return $this->role === 'admin';
        return $this->hasRole('admin');
    }

    public function isFilho(): bool
    {
        //return $this->role === 'filho';
        return $this->hasRole('filho');
    }

    public function canManageFilhos(): bool
    {
       // return in_array($this->role, ['admin', 'manager']);

        return $this->hasPermissionTo('manage_filhos');
    }

    public function canCreateOrders(): bool
    {
       // return in_array($this->role, ['admin', 'manager', 'operator']);

        return $this->hasPermissionTo('manage_orders');
    }
}

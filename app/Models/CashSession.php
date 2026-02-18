<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class CashSession extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id', 'device_id', 'opened_at', 'closed_at', 
        'opening_balance', 'calculated_balance', 'counted_balance', 
        'difference', 'status', 'notes'
    ];

    public function movements() {
        return $this->hasMany(CashMovement::class);
    }
    
    public function user() {
        return $this->belongsTo(User::class);
    }
}

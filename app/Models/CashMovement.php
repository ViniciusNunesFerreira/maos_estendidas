<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class CashMovement extends Model
{
    use HasUuid;
    protected $fillable = ['cash_session_id', 'order_id', 'user_id', 'type', 'amount', 'payment_method', 'description'];

}

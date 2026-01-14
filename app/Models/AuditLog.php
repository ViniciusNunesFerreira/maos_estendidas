<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class AuditLog extends Model
{
    use HasUuid;

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'auditable_type', 'auditable_id', 'action', 
        'old_values', 'new_values', 'ip_address', 
        'user_agent', 'url', 'method', 'tags'
    ];

    // ESTA PARTE É ESSENCIAL
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags'       => 'array',
    ];
}

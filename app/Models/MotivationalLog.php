<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MotivationalLog extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'message_hash',
        'filho_id'
    ];

    public function filho(): BelongsTo
    {
        return $this->belongsTo(Filho::class);
    }
}

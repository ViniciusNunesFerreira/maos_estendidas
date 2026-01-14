<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logActivity('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->logActivity('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->getAttributes(), null);
        });
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }


    protected function logActivity(string $action, ?array $old, ?array $new): void
    {
        // Filtra dados sensíveis (como passwords) e garante que sejam arrays puros
        $oldValues = $old ? array_diff_key($old, array_flip(['password', 'remember_token'])) : null;
        $newValues = $new ? array_diff_key($new, array_flip(['password', 'remember_token'])) : null;

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => $this->getMorphClass(), // Melhor que get_class para polimorfismo
            'auditable_id' => $this->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }
}
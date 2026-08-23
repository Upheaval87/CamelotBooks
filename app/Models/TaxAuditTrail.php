<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxAuditTrail extends Model
{
    use TenantScoped;

    protected $table = 'tax_audit_trail';

    protected $fillable = [
        'company_id',
        'user_id',
        'acted_at',
        'entity_kind',
        'entity_id',
        'field',
        'old_value',
        'new_value',
        'reason',
        'approval',
        'ip',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(int $companyId, int $userId, string $entityKind, int $entityId, ?string $field, mixed $oldValue, mixed $newValue, ?string $reason = null, ?string $approval = null): self
    {
        return static::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'acted_at' => now(),
            'entity_kind' => $entityKind,
            'entity_id' => $entityId,
            'field' => $field,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'reason' => $reason,
            'approval' => $approval,
        ]);
    }
}

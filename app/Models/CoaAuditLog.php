<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoaAuditLog extends Model
{
    use TenantScoped;

    protected $table = 'coa_audit_trail';
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'account_id',
        'action',
        'old_values',
        'new_values',
        'reason',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DEACTIVATED = 'deactivated';
    public const ACTION_REACTIVATED = 'reactivated';
    public const ACTION_RECLASSIFIED = 'reclassified';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function log(
        int $companyId,
        int $accountId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $userId = null
    ): static {
        return static::create([
            'company_id' => $companyId,
            'account_id' => $accountId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

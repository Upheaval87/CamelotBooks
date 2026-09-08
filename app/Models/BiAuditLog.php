<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class BiAuditLog extends Model
{
    use TenantScoped;

    protected $table = 'bi_audit_log';

    public const UPDATED_AT = null;
    public const CREATED_AT = 'created_at';

    protected $fillable = [
        'company_id',
        'action',
        'group_key',
        'key',
        'old_value',
        'new_value',
        'user_id',
    ];

    public static function log(
        int $companyId,
        string $action,
        ?string $groupKey = null,
        ?string $key = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?int $userId = null
    ): void {
        static::create([
            'company_id' => $companyId,
            'action' => $action,
            'group_key' => $groupKey,
            'key' => $key,
            'old_value' => $oldValue === null ? null : json_encode($oldValue),
            'new_value' => $newValue === null ? null : json_encode($newValue),
            'user_id' => $userId,
        ]);
    }

    public function scopeForCompany($q, int $companyId)
    {
        return $q->where('company_id', $companyId);
    }
}
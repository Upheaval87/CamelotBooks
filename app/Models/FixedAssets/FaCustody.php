<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaCustody extends Model
{
    use TenantScoped;

    protected $table = 'fa_custody';

    protected $fillable = [
        'company_id',
        'asset_id',
        'from_custodian',
        'to_custodian',
        'handover_date',
        'reason',
        'condition_notes',
        'handed_by',
        'received_by',
    ];

    protected $casts = [
        'handover_date' => 'date',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('handover_date')->limit($limit);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function handoverUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'handed_by');
    }

    public function receiverUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }
}

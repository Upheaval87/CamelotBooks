<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiSetting extends Model
{
    use TenantScoped;

    protected $table = 'bi_settings';

    public $timestamps = false;

    public const GROUPS = ['scenario', 'allocation', 'assumptions', 'pricing'];

    protected $fillable = [
        'company_id',
        'group_key',
        'key',
        'value',
        'version',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'value' => 'array',
        'version' => 'integer',
        'updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($q, int $companyId)
    {
        return $q->where('company_id', $companyId);
    }
}
<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiExpenseClass extends Model
{
    use TenantScoped;

    protected $table = 'bi_expense_classes';

    public const CREATED_AT = null;

    public const CLASS_FIXED = 'fixed';
    public const CLASS_VARIABLE = 'variable';

    public const CLASSES = [self::CLASS_FIXED, self::CLASS_VARIABLE];

    protected $fillable = [
        'company_id',
        'account_id',
        'class',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
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
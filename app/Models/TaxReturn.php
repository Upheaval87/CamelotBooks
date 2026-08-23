<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxReturn extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'tax_type_id',
        'period_id',
        'status',
        'output_tax',
        'input_tax',
        'adjustments',
        'net_payable',
        'filed_date',
        'reference',
        'prepared_by',
        'approved_by',
        'version',
    ];

    protected $casts = [
        'output_tax' => 'decimal:2',
        'input_tax' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'filed_date' => 'date',
        'version' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TaxReturnLine::class, 'return_id');
    }
}

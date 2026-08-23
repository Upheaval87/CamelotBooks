<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhtCertificate extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'cert_number',
        'supplier_id',
        'tax_code_id',
        'period_id',
        'gross',
        'wht_amount',
        'rate_pct',
        'status',
        'issued_date',
    ];

    protected $casts = [
        'gross' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'rate_pct' => 'decimal:4',
        'issued_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'supplier_id');
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxPeriod extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'tax_type_id',
        'label',
        'start_date',
        'end_date',
        'status',
        'filing_due_date',
        'filed_date',
        'payment_date',
        'reference',
        'locked',
        'version',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'filing_due_date' => 'date',
        'filed_date' => 'date',
        'payment_date' => 'date',
        'locked' => 'boolean',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(TaxTransaction::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(TaxAdjustment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TaxPayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(TaxReturn::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestForQuotation extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'purchase_requisition_id',
        'rfq_number',
        'date',
        'status',
        'deadline',
        'memo',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'deadline' => 'date',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_QUOTED = 'quoted';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RfqLine::class, 'request_for_quotation_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(RfqVendorQuote::class, 'request_for_quotation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

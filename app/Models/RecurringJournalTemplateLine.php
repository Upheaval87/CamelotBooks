<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalTemplateLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'rjt_id', 'account_id', 'branch_id',
        'cost_center_id', 'description', 'debit', 'credit', 'memo', 'line_type',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringJournalTemplate::class, 'rjt_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}

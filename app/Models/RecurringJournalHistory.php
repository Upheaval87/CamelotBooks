<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalHistory extends Model
{
    use TenantScoped;

    protected $table = 'recurring_journal_history';

    protected $fillable = [
        'company_id', 'recurring_journal_template_id', 'recurring_journal_run_id',
        'action', 'description', 'actor_type', 'actor_id', 'metadata', 'happened_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'happened_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringJournalTemplate::class, 'recurring_journal_template_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RecurringJournalRun::class, 'recurring_journal_run_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

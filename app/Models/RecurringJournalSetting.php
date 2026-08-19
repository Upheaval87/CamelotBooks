<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalSetting extends Model
{
    use TenantScoped;

    protected $table = 'recurring_journal_settings';

    protected $fillable = [
        'company_id', 'numbering_pattern', 'approval_required',
        'approval_threshold', 'auto_post_rules', 'notification_email',
        'block_locked_periods', 'default_suspense_account_id',
    ];

    protected $casts = [
        'approval_required' => 'boolean',
        'approval_threshold' => 'decimal:2',
        'auto_post_rules' => 'array',
        'block_locked_periods' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function defaultSuspenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_suspense_account_id');
    }

    public static function forCompany(int $companyId): self
    {
        return static::firstOrCreate(['company_id' => $companyId], [
            'numbering_pattern' => 'RJV-{yyyy}-{seq:6}',
            'approval_required' => false,
            'approval_threshold' => 0,
            'notification_email' => 'after_posting',
            'block_locked_periods' => true,
        ]);
    }
}

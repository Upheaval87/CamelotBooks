<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAuditLog extends Model
{
    use TenantScoped;

    public const UPDATED_AT = null;

    public const ACTION_VIEW = 'VIEW';
    public const ACTION_GENERATE = 'GENERATE';
    public const ACTION_PDF = 'PDF';
    public const ACTION_EXCEL = 'EXCEL';
    public const ACTION_PRINT = 'PRINT';
    public const ACTION_EMAIL = 'EMAIL';
    public const ACTION_SCHEDULE = 'SCHEDULE';

    protected $table = 'report_audit_log';

    protected $fillable = [
        'user_id',
        'acted_at',
        'report_key',
        'action',
        'filters',
        'output_format',
        'recipient',
    ];

    protected $casts = [
        'filters' => 'array',
        'acted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a report action.
     */
    public static function log(
        int $userId,
        int $companyId,
        string $reportKey,
        string $action,
        array $filters,
        ?string $outputFormat = null,
        ?string $recipient = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'acted_at' => now(),
            'report_key' => $reportKey,
            'action' => $action,
            'filters' => $filters,
            'output_format' => $outputFormat,
            'recipient' => $recipient,
        ]);
    }
}

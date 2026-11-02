<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One email dispatch attempt for a quotation (§2.2). The row is created
 * `running` before the pipeline starts and is polled by the dispatch modal /
 * dock; it is the durable record behind the four-stage progress UI.
 */
class QuotationEmailJob extends Model
{
    use TenantScoped;

    /**
     * The table is the generic `email_jobs`, NOT Eloquent's pluralization of
     * this class name — the column prefix is the document type, and the same
     * table is reserved for the next dispatchable document.
     */
    protected $table = 'email_jobs';

    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    /** §3.2 stage labels, in order. */
    public const STAGES = ['preparing', 'uploading', 'delivering', 'recording'];

    public const STAGE_LABELS = [
        'preparing' => 'Preparing PDF document',
        'uploading' => 'Uploading to mail server',
        'delivering' => 'Delivering to recipient mailbox',
        'recording' => 'Recording email dispatch audit',
    ];

    /** Progress percentage per stage — the shine bar in the modal. */
    public const STAGE_PERCENT = [
        'preparing' => 22,
        'uploading' => 48,
        'delivering' => 76,
        'recording' => 94,
    ];

    protected $fillable = [
        'company_id',
        'quotable_type',
        'quotable_id',
        'recipient',
        'stage',
        'status',
        'error_code',
        'error_message',
        'attempts',
        'dispatched_by',
        'cancel_requested_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'cancel_requested_at' => 'datetime',
    ];

    public function quotable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isCancelled(): bool
    {
        return $this->cancel_requested_at !== null && $this->status === self::STATUS_RUNNING;
    }

    /** Client-facing status — adds the derived `cancelled` state of §4.4. */
    public function clientStatus(): string
    {
        return $this->isCancelled() ? 'cancelled' : $this->status;
    }

    public function stageLabel(): string
    {
        return self::STAGE_LABELS[$this->stage] ?? self::STAGE_LABELS['preparing'];
    }

    public function percent(): int
    {
        if ($this->status === self::STATUS_SUCCEEDED) {
            return 100;
        }

        if ($this->status === self::STATUS_FAILED || $this->isCancelled()) {
            return self::STAGE_PERCENT[$this->stage] ?? 22;
        }

        return self::STAGE_PERCENT[$this->stage] ?? 22;
    }
}
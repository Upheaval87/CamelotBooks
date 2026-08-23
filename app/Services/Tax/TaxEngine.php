<?php

namespace App\Services\Tax;

use App\Models\Account;
use App\Models\TaxAuditTrail;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxExemption;
use App\Models\TaxJurisdiction;
use App\Models\TaxPeriod;
use App\Models\TaxRecognitionRule;
use App\Models\TaxRegistration;
use App\Models\TaxTransaction;
use App\Models\TaxType;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaxEngine
{
    public function __construct(
        protected JournalPostingEngine $postingEngine,
    ) {}

    // ──────────────────────────────────────────────────────────────────
    // §1.13 — Public calling contract
    // ──────────────────────────────────────────────────────────────────

    /**
     * Calculate tax for a document line and persist a tax_transaction row.
     *
     * @param  array $context  Must contain:
     *   company_id, user_id, source_kind, source_id (optional),
     *   tax_code_id, base_amount, side (OUTPUT|INPUT),
     *   optional: exemption_id, apportionment_pct, jurisdiction_id,
     *   date (defaults today), round_line (bool), branch_id, cost_center_id,
     *   skip_posting (bool — persist tax_transaction only, no JE)
     */
    public function calculateAndPostTax(array $context): TaxTransaction
    {
        $this->validateContext($context);

        $companyId = $context['company_id'];
        $taxCode   = TaxCode::with('taxType')->findOrFail($context['tax_code_id']);
        $date      = $context['date'] ?? now()->toDateString();

        // Resolve the applicable rate
        $rateRow = $taxCode->activeRate($date);
        if (!$rateRow) {
            throw new InvalidArgumentException("No active rate for tax code '{$taxCode->code}' on {$date}.");
        }

        // Determine treatment
        $treatment = strtoupper($taxCode->treatment);

        // Build the base calculation
        $baseAmount = (float) $context['base_amount'];
        $side       = strtoupper($context['side']);

        // Calculate tax amount based on treatment
        $calc = $this->computeTax($baseAmount, (float) $rateRow->rate_pct, $treatment, $taxCode->price_basis);

        // Apply exemption if present (§1.10)
        $exemptionId     = $context['exemption_id'] ?? null;
        $exemptionReason = null;
        $taxAmount       = $calc['tax_amount'];

        if ($exemptionId) {
            $exemption = TaxExemption::findOrFail($exemptionId);
            $taxAmount = 0;
            $exemptionReason = $exemption->reason ?? $exemption->name;
        }

        // Apply apportionment if present (§1.10)
        $apportionmentPct      = null;
        $recoverableTaxAmount  = null;
        if (isset($context['apportionment_pct']) && $side === 'INPUT') {
            $apportionmentPct     = (float) $context['apportionment_pct'];
            $recoverableTaxAmount = round($taxAmount * $apportionmentPct / 100, 2);
        }

        // Reverse charge handling (§1.11)
        if ($treatment === 'REVERSE_CHARGE') {
            // Both output and input sides
            $this->handleReverseCharge($context, $taxCode, $rateRow, $baseAmount, $date);
        }

        // Round-trip validation (§1.9)
        $roundMode = $taxCode->rounding_mode ?? 'HALF_UP';
        if ($context['round_line'] ?? true) {
            $taxAmount = $this->roundTax($taxAmount, $roundMode);
            $grossAmount = $calc['gross_amount'];
            $netAmount   = $calc['net_amount'];
        } else {
            $grossAmount = $calc['gross_amount'];
            $netAmount   = $calc['net_amount'];
        }

        // Resolve GL account
        $glAccountId = null;
        if ($side === 'OUTPUT') {
            $glAccountId = $taxCode->gl_output_acct;
        } elseif ($side === 'INPUT') {
            $glAccountId = $taxCode->gl_input_acct;
        } elseif (in_array($treatment, ['REVERSE_CHARGE_OUT', 'REVERSE_CHARGE_IN'])) {
            $glAccountId = $taxCode->gl_payable_acct;
        }

        // Resolve recognition basis
        $recognitionBasis = $this->resolveRecognitionBasis($companyId, $taxCode->tax_type_id);
        $recognizedAt     = null;
        if ($recognitionBasis === 'INVOICE') {
            $recognizedAt = now();
        }

        // Resolve or create period
        $period = $this->resolveOrCreatePeriod($companyId, $taxCode->tax_type_id, $date);

        // Persist tax_transaction
        $txn = TaxTransaction::create([
            'company_id'              => $companyId,
            'period_id'               => $period->id,
            'tax_code_id'             => $taxCode->id,
            'rate_pct'                => $rateRow->rate_pct,
            'side'                    => $side,
            'source_kind'             => strtoupper($context['source_kind'] ?? 'MANUAL'),
            'source_id'               => $context['source_id'] ?? null,
            'base_amount'             => $baseAmount,
            'tax_amount'              => $taxAmount,
            'gross_amount'            => $grossAmount,
            'net_amount'              => $netAmount,
            'exemption_id'            => $exemptionId,
            'exemption_reason'        => $exemptionReason,
            'apportionment_pct'       => $apportionmentPct,
            'recoverable_tax_amount'  => $recoverableTaxAmount,
            'jurisdiction_id'         => $context['jurisdiction_id'] ?? $taxCode->jurisdiction_id,
            'gl_account_id'           => $glAccountId,
            'recognition_basis'       => $recognitionBasis,
            'recognized_at'           => $recognizedAt,
            'is_reversal'             => false,
            'status'                  => 'POSTED',
        ]);

        // Audit trail (§1.8)
        TaxAuditTrail::log(
            $companyId,
            $context['user_id'],
            'tax_transaction',
            $txn->id,
            null,
            null,
            [
                'tax_code'  => $taxCode->code,
                'base'      => $baseAmount,
                'tax'       => $taxAmount,
                'side'      => $side,
                'rate'      => $rateRow->rate_pct,
            ],
            $context['reason'] ?? null,
            'SYSTEM'
        );

        return $txn;
    }

    /**
     * Post tax JE lines for a document — called by BillService/InvoiceService
     * inside their existing DB::transaction (§1.14 atomicity).
     *
     * @param  array $context  Must contain: company_id, user_id, date,
     *   source_module, reference, memo, lines (array of TaxTransaction),
     *   optional: branch_id
     */
    public function postTaxJournal(array $context): ?\App\Models\JournalEntry
    {
        $taxLines = $context['lines'] ?? [];

        if (empty($taxLines)) {
            return null;
        }

        $companyId = $context['company_id'];
        $jeLines   = [];
        $totals    = ['OUTPUT' => 0, 'INPUT' => 0];

        foreach ($taxLines as $taxTxn) {
            $side = strtoupper($taxTxn->side);

            // For OUTPUT: DR Tax Receivable, CR Tax Payable
            // For INPUT:  DR Tax Payable, CR Tax Receivable
            // Standard split: one line per side
            if ($taxTxn->tax_amount <= 0) {
                continue;
            }

            if ($side === 'OUTPUT') {
                $receivableAcct = $this->resolveAccount($companyId, 'tax_receivable', '1150');
                $payableAcct    = $this->resolveAccount($companyId, 'tax_payable', '2100');

                $jeLines[] = [
                    'account_id'     => $receivableAcct->id,
                    'debit'          => $taxTxn->tax_amount,
                    'credit'         => 0,
                    'memo'           => "{$context['reference']} — {$taxTxn->taxCode->code}",
                    'entity_type'    => $context['source_module'] ?? null,
                    'entity_id'      => $context['source_entity_id'] ?? null,
                    'branch_id'      => $context['branch_id'] ?? null,
                ];
                $jeLines[] = [
                    'account_id'     => $payableAcct->id,
                    'debit'          => 0,
                    'credit'         => $taxTxn->tax_amount,
                    'memo'           => "{$context['reference']} — {$taxTxn->taxCode->code}",
                    'entity_type'    => $context['source_module'] ?? null,
                    'entity_id'      => $context['source_entity_id'] ?? null,
                    'branch_id'      => $context['branch_id'] ?? null,
                ];
                $totals['OUTPUT'] += $taxTxn->tax_amount;

            } elseif ($side === 'INPUT') {
                $receivableAcct = $this->resolveAccount($companyId, 'tax_receivable', '1150');
                $payableAcct    = $this->resolveAccount($companyId, 'tax_payable', '2100');

                $jeLines[] = [
                    'account_id'     => $payableAcct->id,
                    'debit'          => $taxTxn->tax_amount,
                    'credit'         => 0,
                    'memo'           => "{$context['reference']} — {$taxTxn->taxCode->code}",
                    'entity_type'    => $context['source_module'] ?? null,
                    'entity_id'      => $context['source_entity_id'] ?? null,
                    'branch_id'      => $context['branch_id'] ?? null,
                ];
                $jeLines[] = [
                    'account_id'     => $receivableAcct->id,
                    'debit'          => 0,
                    'credit'         => $taxTxn->tax_amount,
                    'memo'           => "{$context['reference']} — {$taxTxn->taxCode->code}",
                    'entity_type'    => $context['source_module'] ?? null,
                    'entity_id'      => $context['source_entity_id'] ?? null,
                    'branch_id'      => $context['branch_id'] ?? null,
                ];
                $totals['INPUT'] += $taxTxn->tax_amount;
            }
        }

        if (empty($jeLines)) {
            return null;
        }

        // Balance check (§1.14)
        $totalDebit  = array_sum(array_column($jeLines, 'debit'));
        $totalCredit = array_sum(array_column($jeLines, 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException(
                "Tax journal is unbalanced by " . number_format(abs($totalDebit - $totalCredit), 2)
            );
        }

        return $this->postingEngine->post([
            'company_id'   => $companyId,
            'created_by'   => $context['user_id'],
            'date'         => $context['date'],
            'source_module' => 'tax',
            'reference'    => $context['reference'],
            'memo'         => $context['memo'] ?? "Tax posting for {$context['reference']}",
            'branch_id'    => $context['branch_id'] ?? null,
            'lines'        => $jeLines,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // §0.1 — Effective-date overlap validation
    // ──────────────────────────────────────────────────────────────────

    public function validateNoOverlappingRates(int $taxCodeId, string $effectiveFrom, ?string $effectiveTo, ?int $excludeId = null): void
    {
        $query = TaxCodeRate::where('tax_code_id', $taxCodeId)
            ->where(function ($q) use ($effectiveFrom, $effectiveTo) {
                $q->where(function ($q2) use ($effectiveFrom, $effectiveTo) {
                    // New period starts before existing ends AND new period ends after existing starts
                    $q2->where('effective_from', '<=', $effectiveFrom);
                    $q2->where(function ($q3) use ($effectiveFrom) {
                        $q3->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', $effectiveFrom);
                    });
                });
                if ($effectiveTo) {
                    $q->orWhere(function ($q2) use ($effectiveTo) {
                        $q2->where('effective_from', '<=', $effectiveTo);
                        $q2->where(function ($q3) use ($effectiveTo) {
                            $q3->whereNull('effective_to')
                               ->orWhere('effective_to', '>=', $effectiveTo);
                        });
                    });
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException(
                "Tax code rate overlaps with an existing active rate for this tax code."
            );
        }
    }

    public function validateNoOverlappingRegistrations(
        int $companyId,
        string $entityKind,
        int $entityId,
        int $taxTypeId,
        int $jurisdictionId,
        string $effectiveFrom,
        ?string $effectiveTo,
        ?int $excludeId = null
    ): void {
        $query = TaxRegistration::where('company_id', $companyId)
            ->where('entity_kind', $entityKind)
            ->where('entity_id', $entityId)
            ->where('tax_type_id', $taxTypeId)
            ->where('jurisdiction_id', $jurisdictionId)
            ->where(function ($q) use ($effectiveFrom, $effectiveTo) {
                $q->where(function ($q2) use ($effectiveFrom) {
                    $q2->where('effective_from', '<=', $effectiveFrom);
                    $q2->where(function ($q3) use ($effectiveFrom) {
                        $q3->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', $effectiveFrom);
                    });
                });
                if ($effectiveTo) {
                    $q->orWhere(function ($q2) use ($effectiveTo) {
                        $q2->where('effective_from', '<=', $effectiveTo);
                        $q2->where(function ($q3) use ($effectiveTo) {
                            $q3->whereNull('effective_to')
                               ->orWhere('effective_to', '>=', $effectiveTo);
                        });
                    });
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException(
                "Tax registration overlaps with an existing registration for this entity in this jurisdiction."
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // §1.9 — Round-trip tax calculation
    // ──────────────────────────────────────────────────────────────────

    public function computeTax(float $baseAmount, float $ratePct, string $treatment, string $priceBasis = 'EXCLUSIVE'): array
    {
        $rate = $ratePct / 100;

        if ($treatment === 'ZERO_RATED' || $treatment === 'EXEMPT' || $ratePct == 0) {
            return [
                'base_amount'  => $baseAmount,
                'tax_amount'   => 0,
                'gross_amount' => $baseAmount,
                'net_amount'   => $baseAmount,
            ];
        }

        if ($priceBasis === 'INCLUSIVE') {
            // Tax is already included in the base amount
            $grossAmount = $baseAmount;
            $netAmount   = round($baseAmount / (1 + $rate), 2);
            $taxAmount   = round($grossAmount - $netAmount, 2);
        } else {
            // Tax is added on top
            $netAmount   = $baseAmount;
            $taxAmount   = round($baseAmount * $rate, 2);
            $grossAmount = round($netAmount + $taxAmount, 2);
        }

        return [
            'base_amount'  => $baseAmount,
            'tax_amount'   => $taxAmount,
            'gross_amount' => $grossAmount,
            'net_amount'   => $netAmount,
        ];
    }

    public function roundTax(float $amount, string $mode = 'HALF_UP'): float
    {
        return match ($mode) {
            'HALF_DOWN' => (float) number_format(round($amount, 2, PHP_ROUND_HALF_DOWN), 2, '.', ''),
            'HALF_EVEN' => (float) number_format(round($amount, 2, PHP_ROUND_HALF_EVEN), 2, '.', ''),
            default     => (float) number_format(round($amount, 2, PHP_ROUND_HALF_UP), 2, '.', ''),
        };
    }

    /**
     * Round-trip validation: gross → tax → net → gross must reproduce the original.
     */
    public function validateRoundTrip(float $amount, float $ratePct, string $priceBasis): bool
    {
        $calc = $this->computeTax($amount, $ratePct, 'STANDARD', $priceBasis);

        if ($priceBasis === 'INCLUSIVE') {
            // From gross, back-calculate net, then re-calculate gross
            $net      = round($amount / (1 + $ratePct / 100), 2);
            $taxBack  = round($amount - $net, 2);
            $grossBack = round($net + $taxBack, 2);
            return abs($grossBack - $amount) < 0.01;
        } else {
            // From net, add tax, verify the sum
            $tax     = round($amount * $ratePct / 100, 2);
            $gross   = round($amount + $tax, 2);
            // Re-derive net from gross
            $netBack = round($gross / (1 + $ratePct / 100), 2);
            return abs($netBack - $amount) < 0.01;
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // §1.11 — Reverse charge
    // ──────────────────────────────────────────────────────────────────

    protected function handleReverseCharge(array $context, TaxCode $taxCode, TaxCodeRate $rateRow, float $baseAmount, string $date): void
    {
        $calc = $this->computeTax($baseAmount, (float) $rateRow->rate_pct, 'STANDARD', $taxCode->price_basis);

        // Determine period
        $period = $this->resolveOrCreatePeriod($context['company_id'], $taxCode->tax_type_id, $date);

        // Create the output side (reverse charge)
        TaxTransaction::create([
            'company_id'     => $context['company_id'],
            'period_id'      => $period->id,
            'tax_code_id'    => $taxCode->id,
            'rate_pct'       => $rateRow->rate_pct,
            'side'           => 'OUTPUT',
            'source_kind'    => strtoupper($context['source_kind'] ?? 'MANUAL'),
            'source_id'      => $context['source_id'] ?? null,
            'base_amount'    => $baseAmount,
            'tax_amount'     => $calc['tax_amount'],
            'gross_amount'   => $calc['gross_amount'],
            'net_amount'     => $calc['net_amount'],
            'jurisdiction_id' => $context['jurisdiction_id'] ?? $taxCode->jurisdiction_id,
            'gl_account_id'  => $taxCode->gl_output_acct,
            'recognition_basis' => 'INVOICE',
            'recognized_at'  => now(),
            'is_reversal'    => false,
            'status'         => 'POSTED',
        ]);

        // Create the input side (reverse charge)
        TaxTransaction::create([
            'company_id'     => $context['company_id'],
            'period_id'      => $period->id,
            'tax_code_id'    => $taxCode->id,
            'rate_pct'       => $rateRow->rate_pct,
            'side'           => 'INPUT',
            'source_kind'    => strtoupper($context['source_kind'] ?? 'MANUAL'),
            'source_id'      => $context['source_id'] ?? null,
            'base_amount'    => $baseAmount,
            'tax_amount'     => $calc['tax_amount'],
            'gross_amount'   => $calc['gross_amount'],
            'net_amount'     => $calc['net_amount'],
            'jurisdiction_id' => $context['jurisdiction_id'] ?? $taxCode->jurisdiction_id,
            'gl_account_id'  => $taxCode->gl_input_acct,
            'recognition_basis' => 'INVOICE',
            'recognized_at'  => now(),
            'is_reversal'    => false,
            'status'         => 'POSTED',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    protected function validateContext(array $context): void
    {
        $required = ['company_id', 'tax_code_id', 'base_amount', 'side'];
        foreach ($required as $key) {
            if (!isset($context[$key]) && !array_key_exists($key, $context)) {
                throw new InvalidArgumentException("Missing required context key: {$key}");
            }
        }

        $validSides = ['OUTPUT', 'INPUT', 'WHT', 'PAYE'];
        if (!in_array(strtoupper($context['side']), $validSides)) {
            throw new InvalidArgumentException("Invalid side: {$context['side']}. Must be one of: " . implode(', ', $validSides));
        }

        if ((float) $context['base_amount'] < 0) {
            throw new InvalidArgumentException("base_amount must not be negative.");
        }
    }

    protected function resolveRecognitionBasis(int $companyId, int $taxTypeId): string
    {
        $rule = TaxRecognitionRule::where('company_id', $companyId)
            ->where('tax_type_id', $taxTypeId)
            ->first();

        return $rule?->basis ?? 'INVOICE';
    }

    protected function resolveOrCreatePeriod(int $companyId, int $taxTypeId, string $date): TaxPeriod
    {
        $period = TaxPeriod::where('company_id', $companyId)
            ->where('tax_type_id', $taxTypeId)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->where('locked', false)
            ->first();

        if (!$period) {
            // Auto-create an open period for the month
            $periodStart = \Carbon\Carbon::parse($date)->startOfMonth()->toDateString();
            $periodEnd   = \Carbon\Carbon::parse($date)->endOfMonth()->toDateString();
            $label       = \Carbon\Carbon::parse($date)->format('M Y');

            $period = TaxPeriod::create([
                'company_id'      => $companyId,
                'tax_type_id'     => $taxTypeId,
                'label'           => $label,
                'start_date'      => $periodStart,
                'end_date'        => $periodEnd,
                'status'          => 'OPEN',
                'filing_due_date' => \Carbon\Carbon::parse($periodEnd)->addDays(25)->toDateString(),
                'locked'          => false,
            ]);
        }

        return $period;
    }

    protected function resolveAccount(int $companyId, string $mappingKey, string $fallbackCode): Account
    {
        $account = \App\Models\DefaultAccountMapping::getAccount($companyId, $mappingKey);
        if (!$account) {
            $account = Account::where('company_id', $companyId)
                ->where('code', $fallbackCode)
                ->first();
        }
        if (!$account) {
            throw new InvalidArgumentException(
                "Account for mapping '{$mappingKey}' (fallback code {$fallbackCode}) not found for this company."
            );
        }
        return $account;
    }
}

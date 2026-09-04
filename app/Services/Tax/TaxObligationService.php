<?php

namespace App\Services\Tax;

use App\Models\TaxObligation;
use App\Models\TaxPeriod;
use App\Models\TaxReturn;
use App\Models\TaxTransaction;
use App\Models\TaxType;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Owns the tax obligation lifecycle. Every transition is gated here; no other
 * service/controller mutates obligation.status (or the lockstep child statuses)
 * outside this service.
 *
 * Status flow (single source of truth):
 *   OPEN → CALCULATING → READY_TO_RECONCILE → RECONCILED
 *   → RETURN_DRAFTED → RETURN_APPROVED → FILED → PAID → CLOSED
 *   REJECTED reachable from RETURN_DRAFTED / RETURN_APPROVED.
 */
class TaxObligationService
{
    /**
     * Return the obligation for a period, creating it (OPEN) if absent.
     */
    public function forPeriod(int $companyId, TaxPeriod $period): TaxObligation
    {
        return TaxObligation::firstOrCreate(
            [
                'company_id'  => $companyId,
                'tax_type_id' => $period->tax_type_id,
                'period_id'   => $period->id,
            ],
            ['status' => TaxObligation::STATUS_OPEN]
        );
    }

    /**
     * Called by the engine whenever a tax transaction is posted for a period.
     * Moves the obligation toward READY_TO_RECONCILE once all its period
     * transactions are POSTED.
     */
    public function reconcileAfterTransaction(int $companyId, int $periodId, int $taxTypeId): TaxObligation
    {
        $period = TaxPeriod::findOrFail($periodId);
        $obligation = TaxObligation::firstOrCreate(
            [
                'company_id'  => $companyId,
                'tax_type_id' => $taxTypeId,
                'period_id'   => $periodId,
            ],
            ['status' => TaxObligation::STATUS_OPEN]
        );

        if ($obligation->status === TaxObligation::STATUS_OPEN) {
            $this->setStatus($obligation, TaxObligation::STATUS_CALCULATING, null, 'First tax transaction posted.');
        }

        // CALCULATING → READY_TO_RECONCILE now that all period txn are POSTED.
        if ($obligation->status === TaxObligation::STATUS_CALCULATING) {
            $unposted = TaxTransaction::where('company_id', $companyId)
                ->where('period_id', $periodId)
                ->where('status', '!=', 'POSTED')
                ->exists();

            if (! $unposted) {
                $this->setStatus($obligation, TaxObligation::STATUS_READY_TO_RECONCILE);
            }
        }

        return $obligation->fresh();
    }

    /**
     * READY_TO_RECONCILE → RECONCILED. Blocks when the working-paper variance is
     * non-zero and not waived. $variance should be the reconciliation screen's
     * reported variance (calculated − posted) for the period.
     */
    public function reconcile(int $companyId, int $periodId, float $variance, ?int $userId = null, bool $waive = false, ?string $waiveReason = null): TaxObligation
    {
        $obligation = $this->assertGated($companyId, $periodId, TaxObligation::STATUS_READY_TO_RECONCILE);

        if (abs($variance) > 0.0001) {
            if (! $waive) {
                $blocked = $obligation->fresh();
                $blocked->update(['blocked_reason' => 'Working-paper variance is not zero and has not been waived.']);
                throw new HttpException(422, 'Cannot reconcile: the working-paper variance is not zero. Waive the variance to proceed.');
            }

            if (! trim((string) $waiveReason)) {
                throw new HttpException(422, 'A reason is required to waive a non-zero variance.');
            }
        }

        DB::transaction(function () use ($obligation, $variance, $userId, $waive, $waiveReason) {
            if (abs($variance) > 0.0001) {
                $obligation->update([
                    'variance_waived'        => true,
                    'variance_waived_reason' => trim((string) $waiveReason),
                    'variance_waived_by'     => $userId,
                    'variance_waived_at'     => now(),
                ]);

                \App\Models\TaxAuditTrail::log(
                    $obligation->company_id,
                    $userId ?? 0,
                    'TAX_OBLIGATION',
                    $obligation->id,
                    'variance_waived',
                    null,
                    'TRUE',
                    'Working-paper variance waived: ' . trim((string) $waiveReason)
                );
            } else {
                $obligation->update(['blocked_reason' => null]);
            }

            $this->setStatus($obligation, TaxObligation::STATUS_RECONCILED, $userId, 'Tax obligation reconciled.');
        });

        return $obligation->fresh();
    }

    /**
     * RECONCILED → RETURN_DRAFTED. Creates the tax return (service-owned) and
     * moves both the obligation and the period into the drafted state.
     */
    public function draftReturn(int $companyId, int $periodId, int $preparedByUserId, ?string $taxTypeCode = null): TaxReturn
    {
        $obligation = $this->assertGated($companyId, $periodId, TaxObligation::STATUS_RECONCILED);

        $returnService = app(TaxReturnService::class);
        $return = $returnService->generateReturn($companyId, $periodId, $preparedByUserId, $taxTypeCode);

        DB::transaction(function () use ($obligation, $periodId, $preparedByUserId) {
            $this->setStatus($obligation, TaxObligation::STATUS_RETURN_DRAFTED, $preparedByUserId, 'Tax return drafted.');
            TaxPeriod::where('id', $periodId)->update(['status' => 'IN_PREPARATION']);
        });

        return $return->fresh();
    }

    /**
     * RETURN_DRAFTED → RETURN_APPROVED. Single gate for approving a return.
     */
    public function approveReturn(int $companyId, int $periodId, int $approvedByUserId): TaxObligation
    {
        $obligation = $this->assertGated($companyId, $periodId, TaxObligation::STATUS_RETURN_DRAFTED);

        $return = TaxReturn::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->whereIn('status', ['draft', 'submitted'])
            ->first();
        if (! $return) {
            throw new HttpException(422, 'No draft tax return exists for this period.');
        }

        DB::transaction(function () use ($obligation, $return, $approvedByUserId) {
            $return->update([
                'status'      => 'approved',
                'approved_by' => $approvedByUserId,
            ]);
            $this->setStatus($obligation, TaxObligation::STATUS_RETURN_APPROVED, $approvedByUserId, 'Tax return approved.');
            TaxPeriod::where('id', $return->period_id)->update(['status' => 'CLOSED', 'locked' => true]);
        });

        return $obligation->fresh();
    }

    /**
     * RETURN_APPROVED → FILED. Sets the return reference.
     */
    public function fileReturn(int $companyId, int $periodId, ?string $reference = null): TaxObligation
    {
        $obligation = $this->assertGated($companyId, $periodId, TaxObligation::STATUS_RETURN_APPROVED);

        $return = TaxReturn::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->where('status', 'approved')
            ->first();
        if (! $return) {
            throw new HttpException(422, 'No approved tax return exists for this period.');
        }

        DB::transaction(function () use ($obligation, $return, $reference) {
            $return->update([
                'status'     => 'filed',
                'filed_date' => now()->toDateString(),
                'reference'  => $reference ?? $return->reference,
            ]);
            $this->setStatus($obligation, TaxObligation::STATUS_FILED, $return->prepared_by ?? 0, 'Tax return filed.');
        });

        return $obligation->fresh();
    }

    /**
     * FILED → PAID. Requires a confirmed/paid tax_payment covering net_payable,
     * or an explicit nil/refund flag on the obligation.
     */
    public function pay(int $companyId, int $periodId, int $userId, ?string $nilOrRefundReason = null): TaxObligation
    {
        $obligation = $this->assertGated($companyId, $periodId, TaxObligation::STATUS_FILED);

        $return = TaxReturn::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->where('status', 'filed')
            ->first();
        if (! $return) {
            throw new HttpException(422, 'No filed tax return exists for this period.');
        }

        $paid = (float) $this->sumConfirmedPayments($companyId, $periodId);
        $netPayable = (float) $return->net_payable;

        if ($paid < $netPayable - 0.0001) {
            if ($nilOrRefundReason === null) {
                $obligation->update(['blocked_reason' => 'Confirmed payments do not yet cover net payable; explicit nil/refund declaration required.']);
                throw new HttpException(
                    422,
                    'Cannot mark paid: confirmed payments cover ' . number_format($paid, 2)
                    . ' but net payable is ' . number_format($netPayable, 2)
                    . '. Record the payment or declare a nil/refund before closing.'
                );
            }

            $obligation->update([
                'nil_or_refund_flag'   => true,
                'nil_or_refund_reason' => trim((string) $nilOrRefundReason),
            ]);
            \App\Models\TaxAuditTrail::log(
                $companyId, $userId, 'TAX_OBLIGATION', $obligation->id,
                'nil_or_refund', null, 'TRUE', 'Nil/refund declared: ' . trim((string) $nilOrRefundReason)
            );
        } elseif ($nilOrRefundReason !== null) {
            $obligation->update(['nil_or_refund_flag' => false, 'nil_or_refund_reason' => null]);
        }

        return $this->setStatus($obligation, TaxObligation::STATUS_PAID, $userId, 'Tax obligation marked paid.')->fresh();
    }

    /**
     * PAID → CLOSED. Explicit sign-off (who + when).
     */
    public function close(int $companyId, int $periodId, int $closedByUserId): TaxObligation
    {
        $obligation = $this->assertGated($companyId, $periodId, TaxObligation::STATUS_PAID);

        DB::transaction(function () use ($obligation, $closedByUserId) {
            $obligation->update(['closed_by' => $closedByUserId, 'closed_at' => now()]);
            $this->setStatus($obligation, TaxObligation::STATUS_CLOSED, $closedByUserId, 'Tax obligation closed.');
            TaxPeriod::where('id', $obligation->period_id)->update(['status' => 'CLOSED', 'locked' => true]);
        });

        return $obligation->fresh();
    }

    /**
     * RETURN_DRAFTED / RETURN_APPROVED → REJECTED (side state).
     */
    public function rejectReturn(int $companyId, int $periodId, int $rejectedByUserId, ?string $reason = null): TaxObligation
    {
        $obligation = TaxObligation::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->whereIn('status', [
                TaxObligation::STATUS_RETURN_DRAFTED,
                TaxObligation::STATUS_RETURN_APPROVED,
            ])
            ->first();

        if (! $obligation) {
            throw new HttpException(404, 'Obligation not found in a rejectable state.');
        }

        $return = TaxReturn::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->latest('id')->first();
        if ($return) {
            $return->update(['status' => 'rejected']);
        }

        return $this->setStatus($obligation, TaxObligation::STATUS_REJECTED, $rejectedByUserId, $reason ?? 'Tax return rejected.')->fresh();
    }

    /**
     * Re-opens a REJECTED return back to RETURN_DRAFTED for correction.
     */
    public function reopenRejected(int $companyId, int $periodId, int $userId, ?string $reason = null): TaxObligation
    {
        $obligation = $this->assertGated($companyId, $periodId, TaxObligation::STATUS_REJECTED);
        $return = TaxReturn::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->where('status', 'rejected')
            ->latest('id')->first();

        return \Illuminate\Support\Facades\DB::transaction(function () use ($obligation, $return, $userId, $reason) {
            if ($return) {
                $return->update(['status' => 'draft']);
            }
            return $this->setStatus($obligation, TaxObligation::STATUS_RETURN_DRAFTED, $userId, $reason ?? 'Rejected return reopened for correction.')->fresh();
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    protected function assertGated(int $companyId, int $periodId, string $expectedStatus): TaxObligation
    {
        $obligation = TaxObligation::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->first();

        if (! $obligation) {
            throw new HttpException(404, 'No tax obligation exists for this period.');
        }

        if ($obligation->status !== $expectedStatus) {
            throw new HttpException(
                422,
                "Obligation is {$obligation->status}; expected {$expectedStatus} to proceed."
            );
        }

        return $obligation;
    }

    protected function sumConfirmedPayments(int $companyId, int $periodId): float
    {
        return (float) \App\Models\TaxPayment::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(status)'), ['confirmed', 'paid'])
            ->sum('amount');
    }

    protected function setStatus(TaxObligation $obligation, string $newStatus, ?int $actorId = null, ?string $reason = null): TaxObligation
    {
        $old = $obligation->status;
        if ($old === $newStatus) {
            return $obligation;
        }

        $obligation->update([
            'status'         => $newStatus,
            'blocked_reason' => null,
        ]);

        \App\Models\TaxAuditTrail::log(
            $obligation->company_id,
            $actorId,
            'TAX_OBLIGATION',
            $obligation->id,
            'status',
            $old,
            $newStatus,
            $reason
        );

        return $obligation;
    }
}
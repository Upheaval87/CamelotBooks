<?php

namespace App\Services\Tax;

use App\Models\TaxAuditTrail;
use App\Models\TaxObligation;
use App\Models\TaxPayment;
use App\Models\TaxReturn;
use App\Models\TaxType;
use App\Models\TaxPeriod;
use Illuminate\Support\Facades\DB;

class TaxPaymentService
{
    public function recordPayment(
        int $companyId,
        array $data,
        int $recordedByUserId,
    ): TaxPayment {
        $taxType = TaxType::findOrFail($data['tax_type_id']);
        abort_unless($taxType->company_id === $companyId, 404);

        $period = TaxPeriod::findOrFail($data['period_id']);
        abort_unless($period->company_id === $companyId, 404);

        $payment = TaxPayment::create([
            'company_id'      => $companyId,
            'tax_type_id'     => $data['tax_type_id'],
            'period_id'       => $data['period_id'],
            'amount'          => $data['amount'],
            'payment_date'    => $data['payment_date'],
            'bank_account_id' => $data['bank_account_id'],
            'payment_ref'     => $data['payment_ref'] ?? null,
            'receipt_number'  => $data['receipt_number'] ?? null,
            'authority'       => $data['authority'] ?? null,
            'recorded_by'     => $recordedByUserId,
            'status'          => 'confirmed',
        ]);

        TaxAuditTrail::log($companyId, $recordedByUserId, 'TAX_PAYMENT', $payment->id, 'status', null, 'CONFIRMED', 'Tax payment recorded.');

        // Lockstep (Step 1c): if the obligation is FILED and confirmed payments now
        // cover the net payable, advance FILED → PAID.
        $this->advancePaidIfCovered($companyId, $period->id, $recordedByUserId);

        return $payment;
    }

    /**
     * When the obligation is FILED, move it to PAID once confirmed payments
     * cover the return's net payable (or a nil/refund is declared). No-op
     * otherwise. Filters enforcement to a single gate (BLOCKED → INFO).
     */
    protected function advancePaidIfCovered(int $companyId, int $periodId, int $recordedByUserId): void
    {
        $obligation = TaxObligation::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->first();

        if (! $obligation || $obligation->status !== TaxObligation::STATUS_FILED) {
            return;
        }

        $return = TaxReturn::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->where('status', 'filed')
            ->latest('id')->first();
        if (! $return) {
            return;
        }

        $paid = $this->getTotalPaidForReturn($companyId, $return->id);
        $covered = $paid >= (float) $return->net_payable - 0.0001;

        if ($covered) {
            app(TaxObligationService::class)
                ->pay($companyId, $periodId, $recordedByUserId, null);
        }
    }

    public function getPaymentsForPeriod(
        int $companyId,
        int $periodId,
        ?int $taxTypeId = null,
    ) {
        $query = TaxPayment::where('company_id', $companyId)
            ->where('period_id', $periodId);

        if ($taxTypeId) {
            $query->where('tax_type_id', $taxTypeId);
        }

        return $query->get();
    }

    public function getTotalPaidForReturn(
        int $companyId,
        int $returnId,
    ): float {
        $return = TaxReturn::findOrFail($returnId);
        abort_unless($return->company_id === $companyId, 404);

        return TaxPayment::where('company_id', $companyId)
            ->where('period_id', $return->period_id)
            ->where('tax_type_id', $return->tax_type_id)
            ->where('status', 'confirmed')
            ->sum('amount');
    }

    public function voidPayment(int $companyId, int $paymentId): TaxPayment
    {
        $payment = TaxPayment::findOrFail($paymentId);
        abort_unless($payment->company_id === $companyId, 404);

        $oldStatus = $payment->status;
        $payment->update(['status' => 'voided']);

        TaxAuditTrail::log($companyId, $payment->recorded_by, 'TAX_PAYMENT', $payment->id, 'status', strtoupper($oldStatus), 'VOIDED', 'Tax payment voided.');

        return $payment->fresh();
    }
}

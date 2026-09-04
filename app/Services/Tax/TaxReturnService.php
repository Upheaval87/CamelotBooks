<?php

namespace App\Services\Tax;

use App\Models\TaxReturn;
use App\Models\TaxReturnLine;
use App\Models\TaxTransaction;
use App\Models\TaxPeriod;
use App\Models\TaxType;
use App\Models\TaxAdjustment;
use App\Models\TaxAuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaxReturnService
{
    public function generateReturn(
        int $companyId,
        int $periodId,
        int $preparedByUserId,
        ?string $taxTypeCode = null,
    ): TaxReturn {
        $period = TaxPeriod::findOrFail($periodId);
        abort_unless($period->company_id === $companyId, 404);

        $taxType = $taxTypeCode
            ? TaxType::where('company_id', $companyId)->where('code', $taxTypeCode)->firstOrFail()
            : $period->taxType;

        $existing = TaxReturn::where('company_id', $companyId)
            ->where('tax_type_id', $taxType->id)
            ->where('period_id', $periodId)
            ->whereNotIn('status', ['rejected'])->first();

        if ($existing) {
            abort(409, 'A return already exists for this period and tax type.');
        }

        $transactions = TaxTransaction::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->whereHas('taxCode', fn ($q) => $q->where('tax_type_id', $taxType->id))
            ->where('status', 'POSTED')
            ->get();

        $outputTax    = $transactions->where('side', 'OUTPUT')->sum('tax_amount');
        $inputTax     = $transactions->where('side', 'INPUT')->sum('tax_amount');
        $recoverable  = $transactions->where('side', 'INPUT')->sum('recoverable_tax_amount');
        $adjustments  = TaxAdjustment::where('company_id', $companyId)
            ->where('period_id', $periodId)
            ->where('status', 'APPROVED')
            ->sum('amount');

        $netPayable = round($outputTax - $inputTax + $adjustments, 2);

        $version = TaxReturn::where('company_id', $companyId)
            ->where('tax_type_id', $taxType->id)
            ->where('period_id', $periodId)
            ->max('version') ?? 0;

        $return = TaxReturn::create([
            'company_id'   => $companyId,
            'tax_type_id'  => $taxType->id,
            'period_id'    => $periodId,
            'status'       => 'draft',
            'output_tax'   => $outputTax,
            'input_tax'    => $inputTax,
            'adjustments'  => $adjustments,
            'net_payable'  => $netPayable,
            'prepared_by'  => $preparedByUserId,
            'version'      => $version + 1,
        ]);

        $this->buildReturnLines($return, $transactions, $outputTax, $inputTax, $recoverable);

        TaxAuditTrail::log($companyId, $preparedByUserId, 'TAX_RETURN', $return->id, 'status', null, 'DRAFT', 'Tax return generated.');

        return $return;
    }

    public function approve(int $companyId, int $returnId, int $approvedByUserId): TaxReturn
    {
        $return = TaxReturn::findOrFail($returnId);
        abort_unless($return->company_id === $companyId, 404);
        abort_unless($return->status === 'submitted', 400, 'Only submitted returns can be approved.');

        $oldStatus = $return->status;
        $return->update([
            'status'      => 'approved',
            'approved_by' => $approvedByUserId,
        ]);

        $return->period->update(['status' => 'CLOSED', 'locked' => true]);

        TaxAuditTrail::log($companyId, $approvedByUserId, 'TAX_RETURN', $return->id, 'status', strtoupper($oldStatus), 'APPROVED', 'Tax return approved.');

        return $return->fresh();
    }

    public function file(int $companyId, int $returnId, ?string $reference = null): TaxReturn
    {
        $return = TaxReturn::findOrFail($returnId);
        abort_unless($return->company_id === $companyId, 404);
        abort_unless(in_array($return->status, ['approved']), 400, 'Only approved returns can be filed.');

        $oldStatus = $return->status;
        $return->update([
            'status'      => 'filed',
            'filed_date'  => Carbon::now()->toDateString(),
            'reference'   => $reference ?? $return->reference,
        ]);

        TaxAuditTrail::log($companyId, $return->prepared_by ?? null, 'TAX_RETURN', $return->id, 'status', strtoupper($oldStatus), 'FILED', 'Tax return filed.');

        return $return->fresh();
    }

    public function reject(int $companyId, int $returnId, int $rejectedByUserId, ?string $reason = null): TaxReturn
    {
        $return = TaxReturn::findOrFail($returnId);
        abort_unless($return->company_id === $companyId, 404);
        abort_unless(in_array($return->status, ['submitted', 'draft']), 400);

        $oldStatus = $return->status;
        $return->update(['status' => 'rejected']);

        TaxAuditTrail::log($companyId, $rejectedByUserId, 'TAX_RETURN', $return->id, 'status', strtoupper($oldStatus), 'REJECTED', $reason ?? 'Tax return rejected.');

        return $return->fresh();
    }

    protected function buildReturnLines(
        TaxReturn $return,
        $transactions,
        float $outputTax,
        float $inputTax,
        float $recoverable,
    ): void {
        $sections = [
            ['section' => 'A', 'label' => 'Total Output Tax',          'amount' => $outputTax],
            ['section' => 'B', 'label' => 'Total Input Tax',           'amount' => $inputTax],
            ['section' => 'C', 'label' => 'Recoverable Input Tax',     'amount' => $recoverable],
            ['section' => 'D', 'label' => 'Net Tax Payable / (Refund)', 'amount' => $return->net_payable],
        ];

        $byCode = $transactions->groupBy(fn ($tx) => $tx->taxCode?->code ?? 'UNKNOWN');
        foreach ($byCode as $code => $codeTxns) {
            $sections[] = [
                'section' => 'E',
                'label'   => "Breakdown — {$code}",
                'amount'  => round($codeTxns->sum('tax_amount'), 2),
            ];
        }

        foreach ($sections as $line) {
            TaxReturnLine::create(array_merge($line, ['return_id' => $return->id]));
        }
    }
}

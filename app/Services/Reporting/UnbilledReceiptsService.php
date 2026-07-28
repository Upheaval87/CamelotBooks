<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\DefaultAccountMapping;
use App\Models\GoodsReceivedNote;
use Illuminate\Support\Collection;

class UnbilledReceiptsService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = GoodsReceivedNote::forCompany($companyId)
            ->where('status', GoodsReceivedNote::STATUS_POSTED)
            ->with(['vendor', 'lines.product', 'purchaseOrder']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $grns = $query->get();

        $lines = [];
        $totalAccrued = 0;

        foreach ($grns as $grn) {
            foreach ($grn->lines as $line) {
                $amount = (float) $line->total_cost;
                $totalAccrued += $amount;
                $lines[] = [
                    'grn_number' => $grn->grn_number,
                    'date' => $grn->date,
                    'vendor' => $grn->vendor->name ?? 'N/A',
                    'po_number' => $grn->purchaseOrder->po_number ?? 'N/A',
                    'product' => $line->product->name ?? 'N/A',
                    'sku' => $line->product->sku ?? '',
                    'quantity' => (float) $line->quantity_received,
                    'unit_cost' => (float) $line->unit_cost,
                    'total_cost' => $amount,
                ];
            }
        }

        $accruedPurchasesAccount = DefaultAccountMapping::getAccount($companyId, 'accrued_purchases');
        $accruedBalance = $accruedPurchasesAccount ? (float) $accruedPurchasesAccount->current_balance : 0;

        return [
            'lines' => $lines,
            'total_unbilled' => $totalAccrued,
            'accrued_purchases_account' => $accruedPurchasesAccount,
            'accrued_balance' => $accruedBalance,
            'variance' => round($totalAccrued - $accruedBalance, 2),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}

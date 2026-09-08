<?php

namespace App\Services\BI;

use App\Models\Bill;
use App\Models\BillLine;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Vendor;
use App\Models\VendorCredit;
use Illuminate\Support\Facades\DB;

class SupplierScorecardService
{
    protected const REALIZED_BILLS = ['posted', 'partially_paid', 'paid', 'overdue'];

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'display_name', 'currency', 'payment_terms', 'payment_terms_days']);

        $bills = Bill::selectRaw('vendor_id, COUNT(*) as cnt, SUM(amount) as spend')
            ->where('company_id', $companyId)
            ->whereIn('status', self::REALIZED_BILLS)
            ->where('bill_date', '>=', $dateFrom)
            ->where('bill_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $credits = VendorCredit::selectRaw('vendor_id, COUNT(*) as cnt, SUM(amount) as amount')
            ->where('company_id', $companyId)
            ->where('status', VendorCredit::STATUS_POSTED)
            ->where('credit_note_date', '>=', $dateFrom)
            ->where('credit_note_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        // On-time receipts: GRN date <= PO expected delivery date, joined via vendor.
        $onTime = GoodsReceivedNote::selectRaw('goods_received_notes.vendor_id, COUNT(*) as on_time')
            ->join('purchase_orders', 'goods_received_notes.purchase_order_id', '=', 'purchase_orders.id')
            ->where('goods_received_notes.company_id', $companyId)
            ->where('goods_received_notes.status', GoodsReceivedNote::STATUS_POSTED)
            ->whereBetween('goods_received_notes.date', [$dateFrom, $dateTo])
            ->whereColumn('goods_received_notes.date', '<=', 'purchase_orders.expected_delivery_date')
            ->when($branchId, fn ($q) => $q->where('goods_received_notes.branch_id', $branchId))
            ->groupBy('goods_received_notes.vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $grnTotal = GoodsReceivedNote::selectRaw('vendor_id, COUNT(*) as cnt')
            ->where('company_id', $companyId)
            ->where('status', GoodsReceivedNote::STATUS_POSTED)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        // Price drift: bill line unit_price vs its PO line unit_price.
        $drift = BillLine::selectRaw('bills.vendor_id, COUNT(*) as cnt,
                SUM(CASE WHEN bill_lines.unit_price > po.unit_price THEN 1 ELSE 0 END) as increased,
                SUM(po.unit_price) as po_total')
            ->join('bills', 'bill_lines.bill_id', '=', 'bills.id')
            ->join('purchase_order_lines AS po', 'bill_lines.purchase_order_line_id', '=', 'po.id')
            ->where('bills.company_id', $companyId)
            ->whereIn('bills.status', self::REALIZED_BILLS)
            ->whereBetween('bills.bill_date', [$dateFrom, $dateTo])
            ->when($branchId, fn ($q) => $q->where('bills.branch_id', $branchId))
            ->groupBy('bills.vendor_id')
            ->get()
            ->keyBy('vendor_id');

        // Open commitments: PO lines not fully received/closed/cancelled.
        $open = PurchaseOrderLine::selectRaw('purchase_orders.vendor_id,
                SUM(purchase_order_lines.quantity * purchase_order_lines.unit_price) as open_amount,
                COUNT(DISTINCT purchase_orders.id) as open_pos')
            ->join('purchase_orders', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.company_id', $companyId)
            ->whereIn('purchase_orders.status', [PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->when($branchId, fn ($q) => $q->where('purchase_orders.branch_id', $branchId))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $rows = [];
        foreach ($vendors as $vendor) {
            $spend = (float) ($bills->get($vendor->id)->spend ?? 0);
            $creditAmt = (float) ($credits->get($vendor->id)->amount ?? 0);
            $grn = (int) ($grnTotal->get($vendor->id)->cnt ?? 0);
            $ot = (int) ($onTime->get($vendor->id)->on_time ?? 0);
            $dr = $drift->get($vendor->id);
            $driftPct = $dr && (float) $dr->po_total > 0
                ? round((float) $dr->increased / (float) $dr->cnt * 100, 1) : null;

            $rows[] = [
                'vendor_id' => (int) $vendor->id,
                'vendor_name' => $vendor->name ?: $vendor->display_name,
                'currency' => $vendor->currency,
                'spend' => round($spend, 2),
                'bill_count' => (int) ($bills->get($vendor->id)->cnt ?? 0),
                'credit_amount' => round($creditAmt, 2),
                'grn_count' => $grn,
                'on_time_count' => $ot,
                'on_time_pct' => $grn > 0 ? round($ot / $grn * 100, 1) : null,
                'quality_pct' => $spend > 0 ? round(max(0, 1 - $creditAmt / $spend) * 100, 1) : 100.0,
                'price_drift_pct' => $driftPct,
                'open_amount' => round((float) ($open->get($vendor->id)->open_amount ?? 0), 2),
                'open_pos' => (int) ($open->get($vendor->id)->open_pos ?? 0),
                'payment_terms_days' => $vendor->payment_terms_days,
            ];
        }

        usort($rows, fn ($a, $b) => $b['spend'] <=> $a['spend']);

        return [
            'rows' => $rows,
            'total_spend' => round(array_sum(array_column($rows, 'spend')), 2),
            'total_open' => round(array_sum(array_column($rows, 'open_amount')), 2),
            'overall_on_time_pct' => $this->overallOnTime($onTime, $grnTotal),
        ];
    }

    protected function overallOnTime($onTime, $grnTotal): ?float
    {
        $grn = 0;
        $ot = 0;
        foreach ($onTime as $row) {
            $ot += (int) $row->on_time;
        }
        foreach ($grnTotal as $row) {
            $grn += (int) $row->cnt;
        }
        return $grn > 0 ? round($ot / $grn * 100, 1) : null;
    }
}
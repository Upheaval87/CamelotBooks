<?php

namespace App\Services\Reporting;

use App\Models\CustomerPaymentAllocation;
use App\Models\Invoice;
use App\Models\PosSale;
use App\Models\SalesReceipt;
use Illuminate\Support\Facades\DB;

class SalesByCustomerService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        $invoices = Invoice::forCompany($companyId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_PAID])
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->select('customer_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $receipts = SalesReceipt::forCompany($companyId)
            ->where('status', 'posted')
            ->where('receipt_date', '>=', $dateFrom)
            ->where('receipt_date', '<=', $dateTo)
            ->select('customer_id', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $posSales = PosSale::forCompany($companyId)
            ->posted()
            ->whereHas('customer')
            ->select('customer_id', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $allCustomerIds = $invoices->keys()->merge($receipts->keys())->merge($posSales->keys())->unique()->filter();
        $customers = \App\Models\Customer::whereIn('id', $allCustomerIds)->get()->keyBy('id');

        $results = [];
        foreach ($allCustomerIds as $cid) {
            $inv = $invoices->get($cid);
            $rec = $receipts->get($cid);
            $pos = $posSales->get($cid);
            $results[] = [
                'customer_id' => $cid,
                'customer_name' => $customers[$cid]->name ?? 'N/A',
                'invoices_total' => $inv ? (float) $inv->total : 0,
                'invoices_count' => $inv ? (int) $inv->count : 0,
                'receipts_total' => $rec ? (float) $rec->total : 0,
                'receipts_count' => $rec ? (int) $rec->count : 0,
                'pos_total' => $pos ? (float) $pos->total : 0,
                'pos_count' => $pos ? (int) $pos->count : 0,
                'grand_total' => ($inv ? (float) $inv->total : 0) + ($rec ? (float) $rec->total : 0) + ($pos ? (float) $pos->total : 0),
            ];
        }

        usort($results, fn ($a, $b) => $b['grand_total'] <=> $a['grand_total']);

        return [
            'customers' => $results,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}

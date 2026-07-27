<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PosSale;
use App\Models\SalesReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesRegisterController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', 'all');

        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->with('customer')
            ->get()
            ->map(fn ($inv) => [
                'date' => $inv->invoice_date,
                'document_number' => $inv->invoice_number,
                'type' => 'Invoice',
                'customer' => $inv->customer->name ?? '—',
                'amount' => $inv->amount,
                'tax' => $inv->tax_total ?? 0,
                'total' => $inv->total,
                'status' => $inv->status,
                'journal_entry_id' => $inv->journal_entry_id,
            ]);

        $posSales = PosSale::where('company_id', $companyId)
            ->where('status', 'posted')
            ->where('created_at', '>=', $dateFrom . ' 00:00:00')
            ->where('created_at', '<=', $dateTo . ' 23:59:59')
            ->with('customer')
            ->get()
            ->map(fn ($sale) => [
                'date' => $sale->created_at->format('Y-m-d'),
                'document_number' => $sale->sale_number,
                'type' => 'POS Sale',
                'customer' => $sale->customer->name ?? 'Walk-in',
                'amount' => $sale->total - ($sale->tax_total ?? 0),
                'tax' => $sale->tax_total ?? 0,
                'total' => $sale->total,
                'status' => $sale->status,
                'journal_entry_id' => $sale->journal_entry_id,
            ]);

        $receipts = SalesReceipt::where('company_id', $companyId)
            ->where('status', 'posted')
            ->where('receipt_date', '>=', $dateFrom)
            ->where('receipt_date', '<=', $dateTo)
            ->with('customer')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->receipt_date,
                'document_number' => $r->receipt_number,
                'type' => 'Sales Receipt',
                'customer' => $r->customer->name ?? 'Walk-in',
                'amount' => $r->subtotal,
                'tax' => $r->tax_total,
                'total' => $r->total,
                'status' => $r->status,
                'journal_entry_id' => $r->journal_entry_id,
            ]);

        $allSales = $invoices->concat($posSales)->concat($receipts);

        if ($type !== 'all') {
            $typeMap = [
                'invoice' => 'Invoice',
                'pos_sale' => 'POS Sale',
                'sales_receipt' => 'Sales Receipt',
            ];
            $allSales = $allSales->where('type', $typeMap[$type] ?? $type);
        }

        $allSales = $allSales->sortBy('date')->values();

        $summary = [
            'count' => $allSales->count(),
            'total_amount' => $allSales->sum('amount'),
            'total_tax' => $allSales->sum('tax'),
            'total_total' => $allSales->sum('total'),
        ];

        return view('accounting.sales-register.index', compact('allSales', 'summary', 'dateFrom', 'dateTo', 'type'));
    }
}

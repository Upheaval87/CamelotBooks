<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\IncomeStatementService;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SalesAnalyticsService
{
    private IncomeStatementService $incomeStatement;

    public function __construct()
    {
        $this->incomeStatement = new IncomeStatementService();
    }

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $is = $this->incomeStatement->generate($companyId, $branchId, $dateFrom, $dateTo);

        $monthlyInvoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(['invoice_date', 'amount'])
            ->get()
            ->groupBy(fn ($inv) => \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m'))
            ->map(fn ($group) => [
                'month' => $group->first()->invoice_date ? \Carbon\Carbon::parse($group->first()->invoice_date)->format('Y-m') : '',
                'count' => $group->count(),
                'total' => $group->sum('amount'),
                'avg_value' => $group->avg('amount'),
            ])
            ->values()
            ->sortBy('month')
            ->values();

        $topCustomers = Invoice::where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['posted', 'paid', 'partially_paid'])
            ->where('invoices.invoice_date', '>=', $dateFrom)
            ->where('invoices.invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('invoices.branch_id', $branchId))
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->selectRaw('customers.id as customer_id, customers.name as customer_name, SUM(invoices.amount) as total_revenue, COUNT(invoices.id) as invoice_count')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $topProducts = InvoiceLine::query()
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_lines.product_id', '=', 'products.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['posted', 'paid', 'partially_paid'])
            ->where('invoices.invoice_date', '>=', $dateFrom)
            ->where('invoices.invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('invoices.branch_id', $branchId))
            ->selectRaw('products.id as product_id, products.name as product_name, products.sku, SUM(invoice_lines.quantity * invoice_lines.unit_price) as total_revenue, SUM(invoice_lines.quantity) as total_quantity')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $invoiceCount = Invoice::where('company_id', $companyId)
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $avgInvoiceTrend = $monthlyInvoices->pluck('avg_value', 'month')->toArray();

        $quotationCount = Schema::hasTable('quotations')
            ? DB::table('quotations')
                ->where('company_id', $companyId)
                ->where('status', '!=', 'draft')
                ->where('quotation_date', '>=', $dateFrom)
                ->where('quotation_date', '<=', $dateTo)
                ->count()
            : 0;

        $conversionRate = $quotationCount > 0 ? round(($invoiceCount / $quotationCount) * 100, 1) : null;

        return [
            'revenue' => [
                'total_income' => $is['total_income'],
                'total_expenses' => $is['total_expenses'],
                'net_income' => $is['net_income'],
            ],
            'monthly_summary' => $monthlyInvoices->toArray(),
            'top_customers' => $topCustomers->toArray(),
            'top_products' => $topProducts->toArray(),
            'invoice_count' => $invoiceCount,
            'avg_invoice_trend' => $avgInvoiceTrend,
            'conversion' => [
                'quotations' => $quotationCount,
                'invoices' => $invoiceCount,
                'rate' => $conversionRate,
            ],
            'labels' => $monthlyInvoices->pluck('month')->toArray(),
            'invoice_count_data' => $monthlyInvoices->pluck('count')->toArray(),
            'invoice_value_data' => $monthlyInvoices->pluck('total')->map(fn ($v) => (float) $v)->toArray(),
        ];
    }
}

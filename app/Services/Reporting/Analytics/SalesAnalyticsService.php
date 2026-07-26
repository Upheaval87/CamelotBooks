<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\IncomeStatementService;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\PosSale;
use App\Models\PosSaleLine;
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

        $monthlyInvoices = $this->getMonthlySales($companyId, $dateFrom, $dateTo, $branchId);
        $topCustomers = $this->getTopCustomers($companyId, $dateFrom, $dateTo, $branchId);
        $topProducts = $this->getTopProducts($companyId, $dateFrom, $dateTo, $branchId);

        $invoiceCount = $this->getSalesCount($companyId, $dateFrom, $dateTo, $branchId);
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

    protected function getMonthlySales(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): \Illuminate\Support\Collection
    {
        $invoiceRows = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(['invoice_date', 'amount'])
            ->get()
            ->map(fn ($inv) => [
                'month' => \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m'),
                'amount' => $inv->amount,
            ]);

        $posRows = PosSale::where('company_id', $companyId)
            ->where('status', 'posted')
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo . ' 23:59:59')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(['created_at', 'total'])
            ->get()
            ->map(fn ($sale) => [
                'month' => \Carbon\Carbon::parse($sale->created_at)->format('Y-m'),
                'amount' => $sale->total,
            ]);

        $allRows = $invoiceRows->concat($posRows);

        return $allRows->groupBy('month')
            ->map(fn ($group) => [
                'month' => $group->first()['month'],
                'count' => $group->count(),
                'total' => $group->sum('amount'),
                'avg_value' => $group->avg('amount'),
            ])
            ->values()
            ->sortBy('month')
            ->values();
    }

    protected function getTopCustomers(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): \Illuminate\Support\Collection
    {
        $invoiceCustomers = DB::table('invoices')
            ->where('invoices.company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->selectRaw('customers.id as customer_id, customers.name as customer_name, SUM(invoices.amount) as total_revenue, COUNT(invoices.id) as sale_count')
            ->groupBy('customers.id', 'customers.name')
            ->get();

        $posCustomers = DB::table('pos_sales')
            ->where('pos_sales.company_id', $companyId)
            ->where('pos_sales.status', 'posted')
            ->where('pos_sales.created_at', '>=', $dateFrom)
            ->where('pos_sales.created_at', '<=', $dateTo . ' 23:59:59')
            ->whereNotNull('pos_sales.customer_id')
            ->when($branchId, fn ($q) => $q->where('pos_sales.branch_id', $branchId))
            ->join('customers', 'pos_sales.customer_id', '=', 'customers.id')
            ->selectRaw('customers.id as customer_id, customers.name as customer_name, SUM(pos_sales.total) as total_revenue, COUNT(pos_sales.id) as sale_count')
            ->groupBy('customers.id', 'customers.name')
            ->get();

        $merged = collect();
        foreach ($invoiceCustomers as $row) {
            $key = $row->customer_id;
            if ($merged->has($key)) {
                $merged[$key]->total_revenue += $row->total_revenue;
                $merged[$key]->invoice_count += $row->sale_count;
            } else {
                $merged[$key] = (object) [
                    'customer_id'   => $row->customer_id,
                    'customer_name' => $row->customer_name,
                    'total_revenue' => $row->total_revenue,
                    'invoice_count' => $row->sale_count,
                ];
            }
        }
        foreach ($posCustomers as $row) {
            $key = $row->customer_id;
            if ($merged->has($key)) {
                $merged[$key]->total_revenue += $row->total_revenue;
                $merged[$key]->invoice_count += $row->sale_count;
            } else {
                $merged[$key] = (object) [
                    'customer_id'   => $row->customer_id,
                    'customer_name' => $row->customer_name,
                    'total_revenue' => $row->total_revenue,
                    'invoice_count' => $row->sale_count,
                ];
            }
        }

        return $merged->sortByDesc('total_revenue')->values()->take(10);
    }

    protected function getTopProducts(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): \Illuminate\Support\Collection
    {
        $invoiceProducts = DB::table('invoice_lines')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_lines.product_id', '=', 'products.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['posted', 'paid', 'partially_paid'])
            ->where('invoices.invoice_date', '>=', $dateFrom)
            ->where('invoices.invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('invoices.branch_id', $branchId))
            ->selectRaw('products.id as product_id, products.name as product_name, products.sku, SUM(invoice_lines.quantity * invoice_lines.unit_price) as total_revenue, SUM(invoice_lines.quantity) as total_quantity')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->get();

        $posProducts = DB::table('pos_sale_lines')
            ->join('pos_sales', 'pos_sale_lines.pos_sale_id', '=', 'pos_sales.id')
            ->join('products', 'pos_sale_lines.product_id', '=', 'products.id')
            ->where('pos_sales.company_id', $companyId)
            ->where('pos_sales.status', 'posted')
            ->where('pos_sales.created_at', '>=', $dateFrom)
            ->where('pos_sales.created_at', '<=', $dateTo . ' 23:59:59')
            ->when($branchId, fn ($q) => $q->where('pos_sales.branch_id', $branchId))
            ->selectRaw('products.id as product_id, products.name as product_name, products.sku, SUM(pos_sale_lines.line_total) as total_revenue, SUM(pos_sale_lines.quantity) as total_quantity')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->get();

        $merged = collect();
        foreach ($invoiceProducts as $row) {
            $key = $row->product_id;
            if ($merged->has($key)) {
                $merged[$key]->total_revenue += $row->total_revenue;
                $merged[$key]->total_quantity += $row->total_quantity;
            } else {
                $merged[$key] = (object) [
                    'product_id'    => $row->product_id,
                    'product_name'  => $row->product_name,
                    'sku'           => $row->sku,
                    'total_revenue' => $row->total_revenue,
                    'total_quantity'=> $row->total_quantity,
                ];
            }
        }
        foreach ($posProducts as $row) {
            $key = $row->product_id;
            if ($merged->has($key)) {
                $merged[$key]->total_revenue += $row->total_revenue;
                $merged[$key]->total_quantity += $row->total_quantity;
            } else {
                $merged[$key] = (object) [
                    'product_id'    => $row->product_id,
                    'product_name'  => $row->product_name,
                    'sku'           => $row->sku,
                    'total_revenue' => $row->total_revenue,
                    'total_quantity'=> $row->total_quantity,
                ];
            }
        }

        return $merged->sortByDesc('total_revenue')->values()->take(10);
    }

    protected function getSalesCount(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): int
    {
        $invoiceCount = Invoice::where('company_id', $companyId)
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $posCount = PosSale::where('company_id', $companyId)
            ->where('status', '!=', 'voided')
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo . ' 23:59:59')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        return $invoiceCount + $posCount;
    }
}

<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\IncomeStatementService;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class ProfitabilityAnalyticsService
{
    private IncomeStatementService $incomeStatement;

    public function __construct()
    {
        $this->incomeStatement = new IncomeStatementService();
    }

    public function calculate(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $is = $this->incomeStatement->generate($companyId, $branchId, $dateFrom, $dateTo);
        
        $grossMarginByAccount = $this->getGrossMarginByAccount($companyId, $dateFrom, $dateTo);
        $profitabilityByBranch = $this->getByBranch($companyId, $dateFrom, $dateTo);
        $profitabilityByCostCenter = $this->getByCostCenter($companyId, $dateFrom, $dateTo);
        
        // Revenue and COGS by product (via invoice lines joined to cost of goods sold)
        $marginByProduct = $this->getMarginByProduct($companyId, $dateFrom, $dateTo);
        
        return [
            'income_statement' => $is,
            'gross_margin_by_account' => $grossMarginByAccount,
            'by_branch' => $profitabilityByBranch,
            'by_cost_center' => $profitabilityByCostCenter,
            'by_product' => $marginByProduct,
        ];
    }

    private function getGrossMarginByAccount(int $companyId, string $dateFrom, string $dateTo): array
    {
        $rows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->whereIn('accounts.type', ['income', 'expense'])
            ->where('accounts.sub_type', 'cost_of_goods_sold')
            ->selectRaw('accounts.id, accounts.code, accounts.name, accounts.type, SUM(journal_entry_lines.debit - journal_entry_lines.credit) as net')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->get();
        
        $revenueRows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->where('accounts.type', 'income')
            ->selectRaw('accounts.id, accounts.code, accounts.name, SUM(journal_entry_lines.credit - journal_entry_lines.debit) as revenue')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name')
            ->get();
        
        $results = [];
        foreach ($revenueRows as $rev) {
            $cogs = $rows->where('type', 'expense')->sum('net');
            $results[] = [
                'account_code' => $rev->code,
                'account_name' => $rev->name,
                'revenue' => (float) $rev->revenue,
                'cogs' => abs($cogs),
                'gross_margin' => (float) $rev->revenue - abs($cogs),
                'gross_margin_pct' => $rev->revenue > 0 ? ((float) $rev->revenue - abs($cogs)) / (float) $rev->revenue * 100 : null,
            ];
        }
        
        return $results;
    }

    private function getByBranch(int $companyId, string $dateFrom, string $dateTo): array
    {
        $rows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('branches', 'journal_entry_lines.branch_id', '=', 'branches.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->whereIn('accounts.type', ['income', 'expense'])
            ->selectRaw('
                COALESCE(journal_entry_lines.branch_id, 0) as branch_id,
                COALESCE(branches.name, \'Consolidated\') as branch_name,
                SUM(CASE WHEN accounts.type = \'income\' THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END) as revenue,
                SUM(CASE WHEN accounts.type = \'expense\' THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END) as expenses
            ')
            ->groupByRaw('COALESCE(journal_entry_lines.branch_id, 0)')
            ->get();
        
        $results = [];
        foreach ($rows as $row) {
            $revenue = (float) $row->revenue;
            $expenses = (float) $row->expenses;
            $results[] = [
                'branch_id' => $row->branch_id,
                'branch_name' => $row->branch_name,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_income' => $revenue - $expenses,
                'margin_pct' => $revenue > 0 ? ($revenue - $expenses) / $revenue * 100 : null,
            ];
        }
        
        return $results;
    }

    private function getByCostCenter(int $companyId, string $dateFrom, string $dateTo): array
    {
        $rows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('cost_centers', 'journal_entry_lines.cost_center_id', '=', 'cost_centers.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->whereIn('accounts.type', ['income', 'expense'])
            ->selectRaw('
                COALESCE(journal_entry_lines.cost_center_id, 0) as cost_center_id,
                COALESCE(cost_centers.name, \'Unclassified\') as cost_center_name,
                SUM(CASE WHEN accounts.type = \'income\' THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END) as revenue,
                SUM(CASE WHEN accounts.type = \'expense\' THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END) as expenses
            ')
            ->groupByRaw('COALESCE(journal_entry_lines.cost_center_id, 0)')
            ->get();
        
        $results = [];
        foreach ($rows as $row) {
            $revenue = (float) $row->revenue;
            $expenses = (float) $row->expenses;
            $results[] = [
                'cost_center_id' => $row->cost_center_id,
                'cost_center_name' => $row->cost_center_name,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_income' => $revenue - $expenses,
                'margin_pct' => $revenue > 0 ? ($revenue - $expenses) / $revenue * 100 : null,
            ];
        }
        
        return $results;
    }

    private function getMarginByProduct(int $companyId, string $dateFrom, string $dateTo): array
    {
        // Revenue from invoice lines per product
        $revenue = DB::table('invoice_lines')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_lines.product_id', '=', 'products.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['posted', 'paid', 'partially_paid'])
            ->where('invoices.invoice_date', '>=', $dateFrom)
            ->where('invoices.invoice_date', '<=', $dateTo)
            ->selectRaw('products.id as product_id, products.name as product_name, products.sku, SUM(invoice_lines.quantity * invoice_lines.unit_price) as revenue, SUM(invoice_lines.quantity) as quantity_sold')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->get();
        
        $results = [];
        foreach ($revenue as $rev) {
            $results[] = [
                'product_id' => $rev->product_id,
                'product_name' => $rev->product_name,
                'sku' => $rev->sku,
                'revenue' => (float) $rev->revenue,
                'quantity_sold' => (float) $rev->quantity_sold,
                'avg_price' => $rev->quantity_sold > 0 ? (float) $rev->revenue / (float) $rev->quantity_sold : 0,
            ];
        }
        
        usort($results, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
        
        return $results;
    }
}

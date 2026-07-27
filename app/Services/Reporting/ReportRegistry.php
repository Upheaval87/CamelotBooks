<?php

namespace App\Services\Reporting;

class ReportRegistry
{
    private static ?array $reports = null;

    private static function all(): array
    {
        if (self::$reports !== null) {
            return self::$reports;
        }

        self::$reports = [
            'income_statement' => [
                'key' => 'income_statement',
                'name' => 'Income Statement',
                'category' => 'reports',
                'route' => 'accounting.income-statement.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'balance_sheet' => [
                'key' => 'balance_sheet',
                'name' => 'Balance Sheet',
                'category' => 'reports',
                'route' => 'accounting.balance-sheet.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'equity_statement' => [
                'key' => 'equity_statement',
                'name' => 'Statement of Changes in Equity',
                'category' => 'reports',
                'route' => 'accounting.equity-statement.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'cash_flow' => [
                'key' => 'cash_flow',
                'name' => 'Cash Flow Statement',
                'category' => 'reports',
                'route' => 'accounting.cash-flow.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'trial_balance' => [
                'key' => 'trial_balance',
                'name' => 'Trial Balance',
                'category' => 'reports',
                'route' => 'accounting.trial-balance.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'ar_aging' => [
                'key' => 'ar_aging',
                'name' => 'A/R Aging',
                'category' => 'reports',
                'route' => 'accounting.aging.ar-summary',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'ap_aging' => [
                'key' => 'ap_aging',
                'name' => 'A/P Aging',
                'category' => 'reports',
                'route' => 'accounting.aging.ap-summary',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'financial_ratios' => [
                'key' => 'financial_ratios',
                'name' => 'Financial Ratios',
                'category' => 'analytics',
                'route' => 'analytics.financial-ratios',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'revenue_expense_trends' => [
                'key' => 'revenue_expense_trends',
                'name' => 'Revenue & Expense Trends',
                'category' => 'analytics',
                'route' => 'analytics.revenue-expense-trends',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'sales_analytics' => [
                'key' => 'sales_analytics',
                'name' => 'Sales Analytics',
                'category' => 'analytics',
                'route' => 'analytics.sales',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'sales_register' => [
                'key' => 'sales_register',
                'name' => 'Sales Register',
                'category' => 'reports',
                'route' => 'accounting.sales-register.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'purchasing_analytics' => [
                'key' => 'purchasing_analytics',
                'name' => 'Purchasing Analytics',
                'category' => 'analytics',
                'route' => 'analytics.purchasing',
                'permission' => 'view_reports',
                'feature_flag' => ['analytics', 'purchasing'],
            ],
            'inventory_analytics' => [
                'key' => 'inventory_analytics',
                'name' => 'Inventory Analytics',
                'category' => 'analytics',
                'route' => 'analytics.inventory',
                'permission' => 'view_inventory',
                'feature_flag' => ['analytics', 'inventory'],
            ],
            'profitability_analytics' => [
                'key' => 'profitability_analytics',
                'name' => 'Profitability Analytics',
                'category' => 'analytics',
                'route' => 'analytics.profitability',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'budget_vs_actual_trend' => [
                'key' => 'budget_vs_actual_trend',
                'name' => 'Budget vs Actual Trend',
                'category' => 'analytics',
                'route' => 'analytics.budget-vs-actual-trend',
                'permission' => 'view_budgets',
                'feature_flag' => ['analytics', 'budgets'],
            ],
            'cash_flow_trend' => [
                'key' => 'cash_flow_trend',
                'name' => 'Cash Flow Trend & Projection',
                'category' => 'analytics',
                'route' => 'analytics.cash-flow-trend',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
        ];

        return self::$reports;
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function getByCategory(string $category): array
    {
        return array_filter(self::all(), fn ($r) => $r['category'] === $category);
    }

    public static function getAnalyticsReports(): array
    {
        return self::getByCategory('analytics');
    }

    public static function isAccessible(string $key, $user, int $companyId): bool
    {
        $report = self::get($key);
        if (!$report) return false;

        $flags = (array) ($report['feature_flag'] ?? []);
        foreach ($flags as $flag) {
            if (!\App\Services\FeatureManagement::isEnabled($companyId, $flag)) {
                return false;
            }
        }

        if ($report['permission']) {
            return $user->hasAnyRoleInCompany(['system_admin', 'company_admin', $report['permission']], $companyId);
        }

        return true;
    }

    public static function getAccessible($user, int $companyId, ?string $category = null): array
    {
        $reports = $category ? self::getByCategory($category) : self::all();
        return array_filter($reports, fn ($r) => self::isAccessible($r['key'], $user, $companyId));
    }
}

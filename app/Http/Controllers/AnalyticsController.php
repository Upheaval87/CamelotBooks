<?php

namespace App\Http\Controllers;

use App\Services\Reporting\Analytics\FinancialRatiosService;
use App\Services\Reporting\Analytics\RevenueExpenseTrendService;
use App\Services\Reporting\Analytics\SalesAnalyticsService;
use App\Services\Reporting\Analytics\PurchasingAnalyticsService;
use App\Services\Reporting\Analytics\InventoryAnalyticsService;
use App\Services\Reporting\Analytics\ProfitabilityAnalyticsService;
use App\Services\Reporting\Analytics\CashFlowProjectionService;

class AnalyticsController extends Controller
{
    public function financialRatios()
    {
        $companyId = session('current_company_id');
        $asOfDate = request('as_of_date', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;
        $costCenterId = request('cost_center_id') ?: null;

        $service = new FinancialRatiosService();
        $data = $service->calculate($companyId, $asOfDate, $branchId, $costCenterId);

        return view('analytics.financial-ratios', compact('data', 'asOfDate'));
    }

    public function revenueExpenseTrends()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;
        $costCenterId = request('cost_center_id') ?: null;
        $periods = (int) request('periods', 12);
        $dimension = request('dimension', 'none');

        $service = new RevenueExpenseTrendService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $periods, $branchId, $costCenterId, $dimension);

        return view('analytics.revenue-expense-trends', compact('data', 'dateFrom', 'dateTo', 'periods', 'dimension'));
    }

    public function sales()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;

        $service = new SalesAnalyticsService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $branchId);

        return view('analytics.sales', compact('data', 'dateFrom', 'dateTo'));
    }

    public function purchasing()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;

        $service = new PurchasingAnalyticsService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $branchId);

        return view('analytics.purchasing', compact('data', 'dateFrom', 'dateTo'));
    }

    public function inventory()
    {
        $companyId = session('current_company_id');
        $asOfDate = request('as_of_date', now()->format('Y-m-d'));
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $slowMovingDays = (int) request('slow_moving_days', 90);

        $service = new InventoryAnalyticsService();
        $data = $service->calculate($companyId, $asOfDate, $dateFrom, $dateTo, $slowMovingDays);

        return view('analytics.inventory', compact('data', 'asOfDate', 'dateFrom', 'dateTo', 'slowMovingDays'));
    }

    public function profitability()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;
        $costCenterId = request('cost_center_id') ?: null;

        $service = new ProfitabilityAnalyticsService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $branchId, $costCenterId);

        return view('analytics.profitability', compact('data', 'dateFrom', 'dateTo'));
    }

    public function cashFlowTrend()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;
        $projectionMonths = (int) request('projection_months', 6);

        $service = new CashFlowProjectionService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $branchId, $projectionMonths);

        return view('analytics.cash-flow-trend', compact('data', 'dateFrom', 'dateTo', 'projectionMonths'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\BI\BiRefreshService;
use App\Services\BI\BranchProfitabilityService;
use App\Services\BI\CustomerLifetimeValueService;
use App\Services\BI\EmployeeProductivityService;
use App\Services\BI\TrueTotalCostService;

class BiController extends Controller
{
    protected function withStaleness(array $data): array
    {
        $refreshService = new BiRefreshService();
        $data['lastRefresh'] = $refreshService->getLastRefresh();
        $data['lastRefreshAge'] = $refreshService->getLastRefreshAgeHuman();
        return $data;
    }

    protected function withBranches(array $data): array
    {
        $company = Company::findOrFail(session('current_company_id'));
        $data['currentBranches'] = $company->branches()->where('is_active', true)->orderBy('name')->get();
        return $data;
    }

    public function trueTotalCost()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;

        $service = new TrueTotalCostService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $branchId);

        return view('bi.true-total-cost', $this->withStaleness($this->withBranches($data)));
    }

    public function customerLifetimeValue()
    {
        $companyId = session('current_company_id');
        $branchId = request('branch_id') ?: null;

        $service = new CustomerLifetimeValueService();
        $data = $service->calculate($companyId, $branchId);

        return view('bi.customer-lifetime-value', $this->withStaleness($this->withBranches($data)));
    }

    public function employeeProductivity()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;

        $service = new EmployeeProductivityService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $branchId);

        return view('bi.employee-productivity', $this->withStaleness($this->withBranches($data)));
    }

    public function branchProfitability()
    {
        $companyId = session('current_company_id');
        $dateFrom = request('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $branchId = request('branch_id') ?: null;

        $service = new BranchProfitabilityService();
        $data = $service->calculate($companyId, $dateFrom, $dateTo, $branchId);

        return view('bi.branch-profitability', $this->withStaleness($this->withBranches($data)));
    }
}

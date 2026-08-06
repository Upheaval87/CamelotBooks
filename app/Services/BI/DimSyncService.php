<?php

namespace App\Services\BI;

use App\Models\Company;
use App\Services\BI\Concerns\MartConnection;
use Illuminate\Support\Facades\DB;

class DimSyncService
{
    use MartConnection;

    public function syncAll(?Company $company = null): void
    {
        $this->syncCompanies($company);
        $this->syncBranches();
        $this->syncCostCenters();
        $this->syncAccounts();
        $this->syncItems();
        $this->syncCustomers();
        $this->syncVendors();
        $this->syncEmployees();
    }

    public function syncCompanies(?Company $company = null): void
    {
        $rows = Company::query()
            ->when($company, fn ($q) => $q->whereKey($company->id))
            ->select('id', 'company_code', 'name', 'base_currency', 'fiscal_year_start_month', 'is_active')
            ->get()
            ->map(fn (Company $c) => [
                'company_key'             => $c->id,
                'company_code'            => $c->company_code,
                'company_name'            => $c->name,
                'base_currency'           => $c->base_currency,
                'fiscal_year_start_month' => $c->fiscal_year_start_month,
                'is_active'               => $c->is_active,
                'synced_at'               => now(),
            ])
            ->toArray();

        $this->martTable('dim_company')->truncate();
        if ($rows) {
            $this->martTable('dim_company')->insert($rows);
        }
    }

    public function syncBranches(): void
    {
        $rows = $this->martTable('branches')
            ->select('id as branch_key', 'company_id as company_key', 'code as branch_code', 'name as branch_name', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        $this->martTable('dim_branch')->truncate();
        if ($rows) {
            $this->martTable('dim_branch')->insert($rows);
        }
    }

    public function syncCostCenters(): void
    {
        $rows = $this->martTable('cost_centers')
            ->select('id as cost_center_key', 'company_id as company_key', 'code as cost_center_code', 'name as cost_center_name', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        $this->martTable('dim_cost_center')->truncate();
        if ($rows) {
            $this->martTable('dim_cost_center')->insert($rows);
        }
    }

    public function syncAccounts(): void
    {
        $rows = $this->martTable('accounts')
            ->select(
                'id as account_key', 'company_id as company_key', 'code as account_code',
                'name as account_name', 'type as account_type', 'sub_type as account_sub_type',
                'is_bank_account', 'is_non_cash', 'cash_flow_section', 'is_active'
            )
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        $this->martTable('dim_account')->truncate();
        if ($rows) {
            $this->martTable('dim_account')->insert($rows);
        }
    }

    public function syncItems(): void
    {
        $rows = $this->martTable('products')
            ->select(
                'id as item_key', 'company_id as company_key', 'sku',
                'name as item_name', 'type as item_type',
                'tracked_as_inventory', 'sales_price', 'purchase_price', 'is_active'
            )
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        $this->martTable('dim_item')->truncate();
        if ($rows) {
            $this->martTable('dim_item')->insert($rows);
        }
    }

    public function syncCustomers(): void
    {
        $rows = $this->martTable('customers')
            ->select('id as customer_key', 'company_id as company_key', 'name as customer_name', 'email', 'currency', 'payment_terms', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        $this->martTable('dim_customer')->truncate();
        if ($rows) {
            $this->martTable('dim_customer')->insert($rows);
        }
    }

    public function syncVendors(): void
    {
        $rows = $this->martTable('vendors')
            ->select('id as vendor_key', 'company_id as company_key', 'name as vendor_name', 'email', 'currency', 'payment_terms', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        $this->martTable('dim_vendor')->truncate();
        if ($rows) {
            $this->martTable('dim_vendor')->insert($rows);
        }
    }

    public function syncEmployees(): void
    {
        $rows = $this->martTable('employees')
            ->select(
                'id as employee_key', 'company_id as company_key',
                'branch_id as branch_key', 'cost_center_id as cost_center_key',
                'employee_number', 'first_name', 'last_name',
                'position', 'department', 'employment_status', 'is_active'
            )
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'full_name' => trim($r->first_name . ' ' . $r->last_name),
                'synced_at' => now(),
            ])
            ->map(fn ($r) => collect($r)->except(['first_name', 'last_name'])->toArray())
            ->toArray();

        $this->martTable('dim_employee')->truncate();
        if ($rows) {
            $this->martTable('dim_employee')->insert($rows);
        }
    }

    public function getSyncCounts(): array
    {
        return [
            'dim_company'      => $this->martTable('dim_company')->count(),
            'dim_branch'       => $this->martTable('dim_branch')->count(),
            'dim_cost_center'  => $this->martTable('dim_cost_center')->count(),
            'dim_account'      => $this->martTable('dim_account')->count(),
            'dim_item'         => $this->martTable('dim_item')->count(),
            'dim_customer'     => $this->martTable('dim_customer')->count(),
            'dim_vendor'       => $this->martTable('dim_vendor')->count(),
            'dim_employee'     => $this->martTable('dim_employee')->count(),
        ];
    }
}

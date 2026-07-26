<?php

namespace App\Services\BI;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class DimSyncService
{
    public function syncAll(): void
    {
        $this->syncCompanies();
        $this->syncBranches();
        $this->syncCostCenters();
        $this->syncAccounts();
        $this->syncItems();
        $this->syncCustomers();
        $this->syncVendors();
        $this->syncEmployees();
    }

    public function syncCompanies(): void
    {
        $rows = Company::query()
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

        DB::table('dim_company')->truncate();
        if ($rows) {
            DB::table('dim_company')->insert($rows);
        }
    }

    public function syncBranches(): void
    {
        $rows = DB::table('branches')
            ->select('id as branch_key', 'company_id as company_key', 'code as branch_code', 'name as branch_name', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        DB::table('dim_branch')->truncate();
        if ($rows) {
            DB::table('dim_branch')->insert($rows);
        }
    }

    public function syncCostCenters(): void
    {
        $rows = DB::table('cost_centers')
            ->select('id as cost_center_key', 'company_id as company_key', 'code as cost_center_code', 'name as cost_center_name', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        DB::table('dim_cost_center')->truncate();
        if ($rows) {
            DB::table('dim_cost_center')->insert($rows);
        }
    }

    public function syncAccounts(): void
    {
        $rows = DB::table('accounts')
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

        DB::table('dim_account')->truncate();
        if ($rows) {
            DB::table('dim_account')->insert($rows);
        }
    }

    public function syncItems(): void
    {
        $rows = DB::table('products')
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

        DB::table('dim_item')->truncate();
        if ($rows) {
            DB::table('dim_item')->insert($rows);
        }
    }

    public function syncCustomers(): void
    {
        $rows = DB::table('customers')
            ->select('id as customer_key', 'company_id as company_key', 'name as customer_name', 'email', 'currency', 'payment_terms', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        DB::table('dim_customer')->truncate();
        if ($rows) {
            DB::table('dim_customer')->insert($rows);
        }
    }

    public function syncVendors(): void
    {
        $rows = DB::table('vendors')
            ->select('id as vendor_key', 'company_id as company_key', 'name as vendor_name', 'email', 'currency', 'payment_terms', 'is_active')
            ->get()
            ->map(fn ($r) => [
                ...((array) $r),
                'synced_at' => now(),
            ])
            ->toArray();

        DB::table('dim_vendor')->truncate();
        if ($rows) {
            DB::table('dim_vendor')->insert($rows);
        }
    }

    public function syncEmployees(): void
    {
        $rows = DB::table('employees')
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

        DB::table('dim_employee')->truncate();
        if ($rows) {
            DB::table('dim_employee')->insert($rows);
        }
    }

    public function getSyncCounts(): array
    {
        return [
            'dim_company'      => DB::table('dim_company')->count(),
            'dim_branch'       => DB::table('dim_branch')->count(),
            'dim_cost_center'  => DB::table('dim_cost_center')->count(),
            'dim_account'      => DB::table('dim_account')->count(),
            'dim_item'         => DB::table('dim_item')->count(),
            'dim_customer'     => DB::table('dim_customer')->count(),
            'dim_vendor'       => DB::table('dim_vendor')->count(),
            'dim_employee'     => DB::table('dim_employee')->count(),
        ];
    }
}

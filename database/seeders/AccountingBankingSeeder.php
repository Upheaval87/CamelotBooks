<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\ApprovalSetting;
use App\Models\ApprovalThreshold;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\DefaultAccountMapping;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\PosPaymentMethod;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Models\Vendor;
use App\Services\Accounting\BankingDepositService;
use App\Services\Accounting\BankService;
use App\Services\Accounting\BillService;
use App\Services\Accounting\InvoiceService;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Accounting\PettyCashService;
use App\Services\Accounting\SalesReceiptService;
use App\Services\Admin\DefaultChartOfAccounts;
use App\Services\Admin\NumberingSequenceService;
use App\Services\FeatureManagement;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Malawian accounting + banking demo seeder.
 *
 * Creates a small, self-contained Malawian company ("Chimwemwe Trading",
 * Lilongwe, MWK) with full master data and ~4-6 weeks of posted activity:
 *   - Opening balances (bank, AR, AP -> retained earnings)
 *   - AR cycle: invoices -> posted sales receipts (clearing to 1050) -> bank deposits
 *   - AP cycle: posted bills
 *   - Banking ops: cheques, bank transfers, petty cash establish/expense/replenish
 *
 * It uses the same lightweight "in-session" company recipe as the repo's feature
 * tests (provisioning_status = pending, tenant models resolve to whatever
 * connection is bound), so it runs on the sqlite test override AND on a live
 * tenant once the company is provisioned.
 *
 * CALLING:
 *   - `php artisan db:seed --class=Database\\Seeders\\AccountingBankingSeeder`
 *     (super-admin run(): finds/creates the company + a demo operator user)
 *   - Programmatically: `(new AccountingBankingSeeder)->seed($companyId, $userId)`
 *     returns ['counts' => [...]] . This is what the feature test uses so it can
 *     supply its own isolated user + company on sqlite.
 */
class AccountingBankingSeeder extends Seeder
{
    public const COMPANY_NAME = 'Chimwemwe Trading';

    /**
     * Artisan/super-admin entry point. Creates (or reuses) the trio of central
     * rows needed to log in and switch into the company, then seeds everything.
     */
    public function run(): void
    {
        $company = $this->findOrCreateCompany();

        $user = User::where('email', 'chimwemwe@camelotbooks.test')->first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Chimwemwe Demo',
                'email' => 'chimwemwe@camelotbooks.test',
            ]);
        }

        $this->ensureAssignment($user, $company);

        $this->seed($company->id, $user->id);

        $this->command?->info("Seeded company '{$company->name}' (id {$company->id}) for user {$user->email}.");
    }

    /**
     * Full seeding entry point used by tests. Returns a counts summary so the
     * caller can assert row counts. Idempotent for a given company id.
     *
     * @return array{company_id:int, counts:array<string,int>}
     */
    public function seed(int $companyId, int $userId): array
    {
        $this->ensureStubUser($userId);
        $this->setupCompany($companyId);
        $accounts = $this->chart($companyId);
        $ids = $this->seedMasterData($companyId, $accounts);
        $this->seedOpeningBalances($companyId, $userId, $accounts, $ids);
        $this->seedApCycle($companyId, $userId, $accounts, $ids);
        $this->seedArCycle($companyId, $userId, $accounts, $ids);
        $this->seedBankingOps($companyId, $userId, $accounts, $ids);
        $this->seedUndepositedReceipts($companyId, $userId, $accounts, $ids);

        return [
            'company_id' => $companyId,
            'counts' => $this->counts($companyId),
        ];
    }

    // ------------------------------------------------------------------ company

    protected function findOrCreateCompany(): Company
    {
        $company = Company::where('company_code', 'CHIM')->first();
        if ($company) {
            return $company;
        }

        return Company::create([
            'name' => self::COMPANY_NAME,
            'legal_name' => 'Chimwemwe Trading (Private) Limited',
            'company_code' => 'CHIM',
            'tax_id' => 'MW-40123456',
            'address' => 'Area 9, Bishop Mackenzie Road',
            'city' => 'Lilongwe',
            'state' => null,
            'country' => 'Malawi',
            'postal_code' => 'P/Bag 302',
            'phone' => '+265 888 222 345',
            'email' => 'info@chimwemwetrading.mw',
            'base_currency' => 'MWK',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_PENDING,
            'branch_limit' => null,
            'branch_count' => 0,
        ]);
    }

    protected function ensureAssignment(User $user, Company $company): void
    {
        $exists = UserCompanyAssignment::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->exists();

        if ($exists) {
            return;
        }

        UserCompanyAssignment::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => 'company_admin',
            'branch_ids' => [],
            'is_active' => true,
        ]);
    }

    /**
     * Ensure the acting (central) user has a stub row in the TENANT users table
     * so journal_entries.created_by / posted_by foreign keys resolve. The tenant
     * users table is just id + timestamps (Phase 2); when running against a real
     * provisioned tenant connection we INSERT IGNORE a stub. In tests the sqlite
     * override means all models share one full-schema DB where the real user row
     * already exists, so this is a no-op (no tenant bound).
     */
    protected function ensureStubUser(int $userId): void
    {
        $connection = TenantConnectionResolver::connectionName();
        if (! $connection) {
            return;
        }

        DB::connection($connection)->table('users')->insertOrIgnore([
            'id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Mirror TenantDefaultsSeeder using Eloquent models so it works on whatever
     * connection the tenant models resolve to (sqlite override in tests, tenant
     * when bound). Chart + mappings + fiscal year + periods + numbering + approvals.
     */
    protected function setupCompany(int $companyId): void
    {
        // Always ensure the periods the seeded documents use are OPEN. This does
        // not depend on the account-guard below so it also works when provisioning
        // already seeded a locked chart (TenantDefaultsSeeder opens only the
        // fiscal-year start period; the demo needs the current + 2 prior months).
        $fiscalYear = FiscalYear::firstOrCreate(
            ['company_id' => $companyId, 'label' => $this->fiscalYearLabel()],
            [
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'status' => 'open',
            ]
        );

        // Open the CURRENT month's period and the two prior months so the
        // pre-period (2 months back) opening entries and mid-window documents
        // all land on an open period. Pin to the 1st before subtracting:
        // subMonths(2) from the 31st rolls July 31 in August and misses June.
        $this->ensureOpenPeriod($companyId, $fiscalYear->id, now()->startOfMonth()->month);
        $this->ensureOpenPeriod($companyId, $fiscalYear->id, now()->startOfMonth()->subMonth()->month);
        $this->ensureOpenPeriod($companyId, $fiscalYear->id, now()->startOfMonth()->subMonths(2)->month);

        if (Account::where('company_id', $companyId)->exists()) {
            return;
        }

        // Approvals: not required, so JournalPostingEngine posts directly.
        ApprovalSetting::updateOrCreate(
            ['company_id' => $companyId],
            ['requires_approval' => false, 'threshold_amount' => 0]
        );
        foreach (ApprovalThreshold::documentTypes() as $type => $_label) {
            ApprovalThreshold::updateOrCreate(
                ['company_id' => $companyId, 'document_type' => $type],
                ['threshold_amount' => 0, 'is_active' => false]
            );
        }

        app(NumberingSequenceService::class)->seedDefaults($companyId);

        // Enable the feature-gated modules the seeded documents need.
        foreach (['banking', 'purchasing', 'pos'] as $feature) {
            FeatureManagement::enable($companyId, $feature);
        }
    }

    protected function fiscalYearLabel(): string
    {
        return now()->format('Y') . '/' . now()->copy()->addYear()->format('Y');
    }

    protected function ensureOpenPeriod(int $companyId, int $fiscalYearId, int $month): void
    {
        $start = now()->create(now()->year, $month, 1)->startOfMonth();
        $label = $start->format('F Y');

        $existing = AccountingPeriod::where('company_id', $companyId)->where('label', $label)->first();
        if ($existing) {
            // Flip locked/closed periods to open (provisioning seeds them locked).
            if ($existing->status !== 'open') {
                $existing->update(['status' => 'open']);
            }
            return;
        }

        AccountingPeriod::create([
            'company_id' => $companyId,
            'fiscal_year_id' => $fiscalYearId,
            'label' => $label,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);
    }

    // ------------------------------------------------------------------ chart

    /**
     * @return array<string,int> account code -> account id
     */
    protected function chart(int $companyId): array
    {
        $created = [];

        // Merge the standard default chart + the supplemental accounts
        // (the same set TenantDefaultsSeeder produces) into one code->row map.
        $byCode = [];
        foreach (DefaultChartOfAccounts::get() as $row) {
            $byCode[$row['code']] = $row;
        }
        foreach ($this->supplementalAccounts() as $row) {
            $byCode[$row['code']] = $row;
        }

        foreach ($byCode as $code => $data) {
            $existing = Account::where('company_id', $companyId)->where('code', $code)->first();
            if ($existing) {
                $created[$code] = $existing->id;
                continue;
            }

            $parentId = null;
            if (! empty($data['parent_code']) && isset($created[$data['parent_code']])) {
                $parentId = $created[$data['parent_code']];
            }

            $account = Account::create([
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'code' => $code,
                'name' => $data['name'],
                'type' => $data['type'],
                'sub_type' => $data['sub_type'],
                'description' => $data['description'] ?? null,
                'opening_balance' => 0,
                'opening_balance_date' => null,
                'currency' => 'MWK',
                'is_bank_account' => ($code === '1000'),
                'is_active' => true,
            ]);

            $created[$code] = $account->id;
        }

        // Default account mappings (defaultCodes maps key -> account code).
        foreach (DefaultAccountMapping::defaultCodes() as $key => $code) {
            if ($code === null || ! isset($created[$code])) {
                continue;
            }
            DefaultAccountMapping::updateOrCreate(
                ['company_id' => $companyId, 'mapping_key' => $key],
                ['account_id' => $created[$code]]
            );
        }

        return $created;
    }

    protected function supplementalAccounts(): array
    {
        return [
            ['code' => '1050', 'name' => 'Undeposited Funds', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1060', 'name' => 'Cash in Drawer', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1320', 'name' => 'Returnable Containers', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1700', 'name' => 'Accumulated Impairment Losses', 'type' => 'asset', 'sub_type' => 'non_current_asset'],
            ['code' => '2150', 'name' => 'Accrued Purchases', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '2350', 'name' => 'Customer Bottle Credits Liability', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '3300', 'name' => 'Revaluation Surplus', 'type' => 'equity', 'sub_type' => 'equity'],
            ['code' => '4050', 'name' => 'Bottle Deposit Revenue', 'type' => 'income', 'sub_type' => 'revenue'],
            ['code' => '6800', 'name' => 'Purchase Price Variance', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '6850', 'name' => 'Inventory Count Variance', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '7100', 'name' => 'Gain/Loss on Disposal of Fixed Assets', 'type' => 'expense', 'sub_type' => 'non_operating_expense'],
        ];
    }

    // ------------------------------------------------------------------ master data

    /**
     * @return array<string,mixed>
     */
    protected function seedMasterData(int $companyId, array $accounts): array
    {
        $branchId = $this->seedBranches($companyId);
        $costCenters = $this->seedCostCenters($companyId);
        $bankAccounts = $this->seedBankAccounts($companyId);

        $customers = $this->seedCustomers($companyId, $branchId);
        $vendors = $this->seedVendors($companyId, $branchId);
        $products = $this->seedProducts($companyId, $branchId, $accounts);
        $employees = $this->seedEmployees($companyId, $branchId);
        $paymentMethods = $this->seedPaymentMethods($companyId, $accounts);

        return [
            'branch_id' => $branchId,
            'cost_centers' => $costCenters,
            'bank_accounts' => $bankAccounts,
            'customers' => $customers,
            'vendors' => $vendors,
            'products' => $products,
            'employees' => $employees,
            'payment_methods' => $paymentMethods,
        ];
    }

    protected function seedBranches(int $companyId): int
    {
        $branch = Branch::where('company_id', $companyId)->where('code', 'HQ')->first();
        if (! $branch) {
            $branch = Branch::create([
                'company_id' => $companyId,
                'name' => 'Head Office',
                'code' => 'HQ',
                'address' => 'Bishop Mackenzie Road, Lilongwe',
                'is_active' => true,
            ]);
        }
        return $branch->id;
    }

    protected function seedCostCenters(int $companyId): array
    {
        $defs = [
            ['name' => 'Retail Sales', 'code' => 'RS'],
            ['name' => 'Procurement', 'code' => 'PR'],
            ['name' => 'Administration', 'code' => 'AD'],
        ];
        $ids = [];
        foreach ($defs as $def) {
            $cc = CostCenter::where('company_id', $companyId)->where('code', $def['code'])->first();
            if (! $cc) {
                $cc = CostCenter::create([
                    'company_id' => $companyId,
                    'name' => $def['name'],
                    'code' => $def['code'],
                    'description' => null,
                    'is_active' => true,
                ]);
            }
            $ids[$def['code']] = $cc->id;
        }
        return $ids;
    }

    protected function seedBankAccounts(int $companyId): array
    {
        $defs = [
            ['code' => '1000', 'name' => 'NBS Bank - Main', 'is_bank_account' => true, 'is_petty_cash' => false],
            ['code' => '1030', 'name' => 'Standard Bank - Savings', 'is_bank_account' => true, 'is_petty_cash' => false],
            ['code' => '1010', 'name' => 'Petty Cash', 'is_bank_account' => false, 'is_petty_cash' => true],
        ];
        $ids = [];
        foreach ($defs as $def) {
            $acct = Account::where('company_id', $companyId)->where('code', $def['code'])->first();
            if ($acct) {
                // Ensure the flags are set even when the account already exists
                // (e.g. 1000 seeded by the chart, 1010 seeded as a plain account).
                $acct->update([
                    'is_bank_account' => $def['is_bank_account'],
                    'is_petty_cash' => $def['is_petty_cash'],
                    'currency' => 'MWK',
                    'is_active' => true,
                    'next_cheque_number' => $acct->next_cheque_number ?? 1,
                    'petty_cash_float' => $acct->petty_cash_float ?? 0,
                ]);
            } else {
                $acct = Account::create([
                    'company_id' => $companyId,
                    'parent_id' => null,
                    'code' => $def['code'],
                    'name' => $def['name'],
                    'type' => 'asset',
                    'sub_type' => 'current_asset',
                    'description' => null,
                    'opening_balance' => 0,
                    'currency' => 'MWK',
                    'is_bank_account' => $def['is_bank_account'],
                    'is_petty_cash' => $def['is_petty_cash'],
                    'is_active' => true,
                    'next_cheque_number' => 1,
                    'petty_cash_float' => 0,
                ]);
            }
            $ids[$def['code']] = $acct->id;
        }
        return $ids;
    }

    protected function seedCustomers(int $companyId, int $branchId): array
    {
        $names = [
            ['name' => 'Grace Banda', 'email' => 'grace.banda@customer.mw', 'phone' => '+265 991 111 001', 'terms' => 'net_30', 'days' => 30],
            ['name' => 'Chisomo Phiri', 'email' => 'chisomo.phiri@customer.mw', 'phone' => '+265 992 222 002', 'terms' => 'net_15', 'days' => 15],
            ['name' => 'Limbani Nyirenda', 'email' => 'limbani.nyirenda@customer.mw', 'phone' => '+265 993 333 003', 'terms' => 'net_30', 'days' => 30],
            ['name' => 'Takondwa Mwale', 'email' => 'takondwa.mwale@customer.mw', 'phone' => '+265 994 444 004', 'terms' => 'due_on_receipt', 'days' => 0],
            ['name' => 'Mary Chilumpha', 'email' => 'mary.chilumpha@customer.mw', 'phone' => '+265 995 555 005', 'terms' => 'net_60', 'days' => 60],
            ['name' => 'Joseph Kachale', 'email' => 'joseph.kachale@customer.mw', 'phone' => '+265 996 666 006', 'terms' => 'net_30', 'days' => 30],
        ];
        $ids = [];
        foreach ($names as $n) {
            $cust = Customer::where('company_id', $companyId)->where('email', $n['email'])->first();
            if (! $cust) {
                $cust = Customer::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'name' => $n['name'],
                    'display_name' => $n['name'],
                    'email' => $n['email'],
                    'phone' => $n['phone'],
                    'billing_address' => 'Lilongwe, Malawi',
                    'shipping_address' => 'Lilongwe, Malawi',
                    'currency' => 'MWK',
                    'payment_terms' => $n['terms'],
                    'payment_terms_days' => $n['days'],
                    'credit_limit' => 500000,
                    'opening_balance' => 0,
                    'is_active' => true,
                ]);
            }
            $ids[] = $cust->id;
        }
        return $ids;
    }

    protected function seedVendors(int $companyId, int $branchId): array
    {
        $names = [
            ['name' => 'National Supplies Co', 'email' => 'apex@vendor.mw', 'phone' => '+265 881 111 101', 'terms' => 'net_30', 'days' => 30],
            ['name' => 'Blantyre Wholesale', 'email' => 'blantyre@vendor.mw', 'phone' => '+265 882 222 102', 'terms' => 'net_15', 'days' => 15],
            ['name' => 'Mzuzu Agri Distributors', 'email' => 'mzuzu@vendor.mw', 'phone' => '+265 883 333 103', 'terms' => 'net_30', 'days' => 30],
            ['name' => 'Lilongwe General Trading', 'email' => 'general@vendor.mw', 'phone' => '+265 884 444 104', 'terms' => 'due_on_receipt', 'days' => 0],
        ];
        $ids = [];
        foreach ($names as $n) {
            $v = Vendor::where('company_id', $companyId)->where('email', $n['email'])->first();
            if (! $v) {
                $v = Vendor::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'name' => $n['name'],
                    'display_name' => $n['name'],
                    'email' => $n['email'],
                    'phone' => $n['phone'],
                    'billing_address' => 'Malawi',
                    'remit_to_address' => 'Malawi',
                    'currency' => 'MWK',
                    'payment_terms' => $n['terms'],
                    'payment_terms_days' => $n['days'],
                    'opening_balance' => 0,
                    'is_active' => true,
                ]);
            }
            $ids[] = $v->id;
        }
        return $ids;
    }

    protected function seedProducts(int $companyId, int $branchId, array $accounts): array
    {
        $revenueId = $accounts['4000'] ?? null;
        $expenseId = $accounts['5000'] ?? null;
        $inventoryId = $accounts['1200'] ?? null;

        $items = [
            ['name' => 'Maize Meal 25kg', 'sku' => 'MM25', 'sales' => 12500, 'purchase' => 9500, 'inventory' => true],
            ['name' => 'Cooking Oil 5L', 'sku' => 'CO5L', 'sales' => 18000, 'purchase' => 14200, 'inventory' => true],
            ['name' => 'Sugar 2kg', 'sku' => 'SU2K', 'sales' => 3200, 'purchase' => 2400, 'inventory' => true],
            ['name' => 'Rice 10kg', 'sku' => 'RC10', 'sales' => 15800, 'purchase' => 12000, 'inventory' => true],
            ['name' => 'Flour 5kg', 'sku' => 'FL5K', 'sales' => 8900, 'purchase' => 6800, 'inventory' => true],
            ['name' => 'Salt 1kg', 'sku' => 'SA1K', 'sales' => 950, 'purchase' => 700, 'inventory' => true],
            ['name' => 'Tea Bags 100s', 'sku' => 'TB100', 'sales' => 4200, 'purchase' => 3100, 'inventory' => true],
            ['name' => 'Bottled Water 500ml (Case)', 'sku' => 'BW500', 'sales' => 2600, 'purchase' => 1900, 'inventory' => true],
            ['name' => 'Delivery Service', 'sku' => 'SVC-DLV', 'sales' => 3000, 'purchase' => 0, 'inventory' => false],
            ['name' => 'Consulting Services', 'sku' => 'SVC-CNS', 'sales' => 25000, 'purchase' => 0, 'inventory' => false],
        ];
        $ids = [];
        foreach ($items as $i) {
            $p = Product::where('company_id', $companyId)->where('sku', $i['sku'])->first();
            if (! $p) {
                $p = Product::create([
                    'company_id' => $companyId,
                    'name' => $i['name'],
                    'description' => $i['name'],
                    'sku' => $i['sku'],
                    'barcode' => null,
                    'type' => $i['inventory'] ? 'goods' : 'service',
                    // The demo intentionally skips inventory management (no PO->GRN
                    // stock layers), so goods are sold as non-inventory-tracked
                    // products to keep invoice posting free of COGS consumption.
                    'tracked_as_inventory' => false,
                    'sales_price' => $i['sales'],
                    'purchase_price' => $i['purchase'],
                    'reorder_point' => 10,
                    'unit_of_measure' => 'unit',
                    'income_account_id' => $revenueId,
                    'expense_account_id' => $expenseId,
                    'inventory_asset_account_id' => $inventoryId,
                    'tax_rate' => 0,
                    'is_taxable' => false,
                    'is_active' => true,
                    'is_assembly' => false,
                ]);
            }
            $ids[$i['sku']] = $p->id;
        }
        return $ids;
    }

    protected function seedEmployees(int $companyId, int $branchId): array
    {
        $people = [
            ['first' => 'Chimwemwe', 'last' => 'Tembo', 'position' => 'Managing Director', 'dept' => 'Executive', 'phone' => '+265 990 000 010'],
            ['first' => 'Thandiwe', 'last' => 'Kayira', 'position' => 'Accountant', 'dept' => 'Finance', 'phone' => '+265 990 000 011'],
            ['first' => 'Kondwani', 'last' => 'Moyo', 'position' => 'Sales Associate', 'dept' => 'Sales', 'phone' => '+265 990 000 012'],
            ['first' => 'Alinafe', 'last' => 'Gondwe', 'position' => 'Procurement Officer', 'dept' => 'Procurement', 'phone' => '+265 990 000 013'],
            ['first' => 'Zikomo', 'last' => 'Banda', 'position' => 'Warehouse Manager', 'dept' => 'Operations', 'phone' => '+265 990 000 014'],
        ];
        $ids = [];
        $n = 1;
        foreach ($people as $p) {
            $emp = Employee::where('company_id', $companyId)->where('national_id', 'MW-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT))->first();
            if (! $emp) {
                $emp = Employee::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'employee_number' => 'EMP-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'first_name' => $p['first'],
                    'last_name' => $p['last'],
                    'email' => strtolower($p['first'] . '.' . $p['last']) . '@chimwemwetrading.mw',
                    'phone' => $p['phone'],
                    'gender' => 'female',
                    'position' => $p['position'],
                    'department' => $p['dept'],
                    'hire_date' => now()->subMonths(8)->toDateString(),
                    'employment_status' => 'active',
                    'employment_type' => 'full_time',
                    'national_id' => 'MW-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                    'marital_status' => 'single',
                    'is_active' => true,
                ]);
            }
            $ids[] = $emp->id;
            $n++;
        }
        return $ids;
    }

    protected function seedPaymentMethods(int $companyId, array $accounts): array
    {
        $undeposited = $accounts['1050'] ?? null;
        $cashClearing = $accounts['1060'] ?? null;
        $mainBank = $accounts['1000'] ?? null;

        $defs = [
            // Method clearing to 1050 so posted receipts sit in Undeposited Funds
            // until a BankDeposit claims them.
            ['name' => 'Bank Transfer (Un-deposited)', 'type' => 'bank_transfer', 'clearing' => $undeposited, 'settlement' => $mainBank, 'ref' => true],
            ['name' => 'Mobile Money (Un-deposited)', 'type' => 'mobile_money', 'clearing' => $undeposited, 'settlement' => $mainBank, 'ref' => true],
            // Cash clears to the cash-in-drawer account (not undeposited).
            ['name' => 'Cash', 'type' => 'cash', 'clearing' => $cashClearing, 'settlement' => null, 'ref' => false],
        ];

        $ids = [];
        foreach ($defs as $i => $d) {
            $pm = PosPaymentMethod::where('company_id', $companyId)->where('name', $d['name'])->first();
            if (! $pm) {
                $pm = PosPaymentMethod::create([
                    'company_id' => $companyId,
                    'name' => $d['name'],
                    'type' => $d['type'],
                    'clearing_account_id' => $d['clearing'],
                    'settlement_bank_account_id' => $d['settlement'],
                    'requires_reference' => $d['ref'],
                    'is_active' => true,
                ]);
            }
            $ids[] = $pm->id;
        }
        return $ids;
    }

    // ------------------------------------------------------------------ opening balances

    protected function seedOpeningBalances(int $companyId, int $userId, array $accounts, array $ids): void
    {
        $je = app(JournalPostingEngine::class);
        $mainBank = $ids['bank_accounts']['1000'];
        $retained = $accounts['3100'] ?? null;
        if (! $retained) {
            return;
        }

        // Idempotency guard: never post a second opening-balance entry.
        if (\App\Models\JournalEntry::where('company_id', $companyId)
            ->where('source_module', 'opening')
            ->where('reference', 'OPEN-001')
            ->exists()) {
            return;
        }

        // One day after the month start (instead of exactly the 1st): on the
        // sqlite test override a `date` cast stores "Y-m-d H:i:s", so a JE dated
        // exactly on a period's first day fails the lexicographic
        // `start_date <= entry_date` comparison in JournalPostingEngine.
        $openingDate = now()->startOfMonth()->subMonths(2)->addDay()->toDateString();

        $lines = [
            // Dr Main Bank (opening float)
            ['account_id' => $mainBank, 'debit' => 1500000, 'credit' => 0, 'memo' => 'Opening balance - NBS Bank Main'],
        ];

        // AR opening balances: Dr AR per customer (uses 1100).
        $ar = $accounts['1100'] ?? null;
        if ($ar) {
            foreach (array_slice($ids['customers'], 0, 3) as $i => $cid) {
                $amount = [150000, 90000, 240000][$i];
                $lines[] = ['account_id' => $ar, 'debit' => $amount, 'credit' => 0, 'memo' => "Opening AR - customer #{$cid}"];
            }
        }

        // AP opening balances: Cr AP per vendor (uses 2000).
        $ap = $accounts['2000'] ?? null;
        if ($ap) {
            foreach (array_slice($ids['vendors'], 0, 2) as $i => $vid) {
                $amount = [180000, 75000][$i];
                $lines[] = ['account_id' => $ap, 'debit' => 0, 'credit' => $amount, 'memo' => "Opening AP - vendor #{$vid}"];
            }
        }

        // Balance into Retained Earnings.
        $debits = array_sum(array_column($lines, 'debit'));
        $credits = array_sum(array_column($lines, 'credit'));
        if ($debits >= $credits) {
            $lines[] = ['account_id' => $retained, 'debit' => 0, 'credit' => round($debits - $credits, 2), 'memo' => 'Opening balance to Retained Earnings'];
        } else {
            $lines[] = ['account_id' => $retained, 'debit' => round($credits - $debits, 2), 'credit' => 0, 'memo' => 'Opening balance to Retained Earnings'];
        }

        $je->post([
            'company_id' => $companyId,
            'created_by' => $userId,
            'date' => $openingDate,
            'source_module' => 'opening',
            'reference' => 'OPEN-001',
            'memo' => 'Company opening balances',
            'lines' => $lines,
        ]);
    }

    // ------------------------------------------------------------------ AP cycle (bills)

    protected function seedApCycle(int $companyId, int $userId, array $accounts, array $ids): void
    {
        $billService = app(BillService::class);
        // Non-inventory expense bills exercise AP + tax without stock movement.
        $expenseId = $accounts['5000'] ?? null;
        $vendorIds = $ids['vendors'];
        $branchId = $ids['branch_id'];

        // Idempotency guard: skip if the first seeded bill already exists.
        if (\App\Models\Bill::where('company_id', $companyId)->where('reference', 'SUP-7781')->exists()) {
            return;
        }

        $specs = [
            ['vendor' => 0, 'date_days_ago' => 12, 'due_days' => 30, 'ref' => 'SUP-7781', 'desc' => 'Office stationery & consumables', 'amount' => 65000, 'tax' => 0],
            ['vendor' => 1, 'date_days_ago' => 6, 'due_days' => 15, 'ref' => 'BW-1209', 'desc' => 'Wholesale restock - assorted goods', 'amount' => 320000, 'tax' => 0],
        ];

        foreach ($specs as $i => $spec) {
            $poBacked = false;
            $bill = $billService->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'vendor_id' => $vendorIds[$spec['vendor']],
                'bill_date' => now()->subDays($spec['date_days_ago'])->toDateString(),
                'due_date' => now()->subDays($spec['date_days_ago'])->addDays($spec['due_days'])->toDateString(),
                'reference' => $spec['ref'],
                'memo' => 'Purchased from ' . Vendor::find($vendorIds[$spec['vendor']])->name,
                'currency' => 'MWK',
                'exchange_rate' => 1,
                'lines' => [[
                    'product_id' => null,
                    'description' => $spec['desc'],
                    'quantity' => 1,
                    'unit_price' => $spec['amount'],
                    'discount' => 0,
                    'tax_rate' => 0,
                    'expense_account_id' => $expenseId,
                    'cost_center_id' => $ids['cost_centers']['PR'] ?? null,
                ]],
            ], $userId);

            // Post the first bill only (the second stays draft to show an open AP).
            if ($i === 0) {
                $billService->post($bill, $userId);
            }
        }
    }

    // ------------------------------------------------------------------ AR cycle (invoices -> receipts -> deposits)

    protected function seedArCycle(int $companyId, int $userId, array $accounts, array $ids): void
    {
        $invoiceService = app(InvoiceService::class);
        $receiptService = app(SalesReceiptService::class);
        $depositService = app(BankingDepositService::class);

        // Idempotency guard: the deposit is the LAST step of the AR chain, so
        // if it exists the whole cycle (invoices + receipts) was already seeded.
        if (\App\Models\BankDeposit::where('company_id', $companyId)->where('reference', 'DEP-WK1')->exists()) {
            return;
        }

        $customerIds = $ids['customers'];
        $productIds = $ids['products'];
        $branchId = $ids['branch_id'];
        $revenueId = $accounts['4000'] ?? null;

        // A small helper stack of invoice line items (product -> qty, price).
        $recipes = [
            ['customer' => 0, 'days_ago' => 20, 'due' => 30, 'ref' => null, 'lines' => [['p' => 'MM25', 'q' => 10, 'price' => 12500]]],
            ['customer' => 1, 'days_ago' => 14, 'due' => 15, 'ref' => 'SO-441', 'lines' => [['p' => 'CO5L', 'q' => 8, 'price' => 18000], ['p' => 'SU2K', 'q' => 15, 'price' => 3200]]],
            ['customer' => 2, 'days_ago' => 7, 'due' => 30, 'ref' => null, 'lines' => [['p' => 'RC10', 'q' => 6, 'price' => 15800], ['p' => 'FL5K', 'q' => 4, 'price' => 8900]]],
        ];

        $invoices = [];
        foreach ($recipes as $r) {
            $lines = [];
            foreach ($r['lines'] as $ln) {
                $lines[] = [
                    'product_id' => $productIds[$ln['p']],
                    'description' => Product::find($productIds[$ln['p']])->name,
                    'quantity' => $ln['q'],
                    'unit_price' => $ln['price'],
                    'discount' => 0,
                    'tax_rate' => 0,
                    'income_account_id' => $revenueId,
                ];
            }
            $invoice = $invoiceService->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customerIds[$r['customer']],
                'invoice_date' => now()->subDays($r['days_ago'])->toDateString(),
                'due_date' => now()->subDays($r['days_ago'])->addDays($r['due'])->toDateString(),
                'reference' => $r['ref'],
                'memo' => 'Goods supplied to ' . Customer::find($customerIds[$r['customer']])->name,
                'currency' => 'MWK',
                'exchange_rate' => 1,
                'lines' => $lines,
            ], $userId);

            $invoiceService->post($invoice, $userId);
            $invoices[] = $invoice;
        }

        // Settlement receipts: collect payment on 2 of the 3 invoices, clearing
        // to the 1050 Undeposited Funds account (Bank Transfer / Mobile Money
        // methods have clearing_account_id = 1050 and no bank_account_id).
        // Method indices from seedPaymentMethods(): 0/1 clear to 1050
        // (undeposited), 2 (Cash) clears to 1060.
        $undepositedPm = $ids['payment_methods'][0] ?? null; // Bank Transfer (Un-deposited)
        $mobilePm = $ids['payment_methods'][1] ?? null;
        $cashPm = $ids['payment_methods'][2] ?? null;

        $settlements = [
            // Two receipts clear to Undeposited Funds (1050) -> claimable by a deposit.
            ['invoice' => 0, 'days_ago' => 18, 'pm' => $undepositedPm],
            ['invoice' => 1, 'days_ago' => 12, 'pm' => $undepositedPm],
            // The latest receipt is CASH -> clears to 1060, never undeposited,
            // so exactly the two older lines are available to deposit.
            ['invoice' => 2, 'days_ago' => 3, 'pm' => $cashPm],
        ];

        foreach ($settlements as $s) {
            $invoice = $invoices[$s['invoice']];
            $amount = (float) $invoice->amount;

            $receipt = $receiptService->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'receipt_date' => now()->subDays($s['days_ago'])->toDateString(),
                'reference' => 'PAY-' . str_pad((string) ($s['invoice'] + 1), 3, '0', STR_PAD_LEFT),
                'memo' => 'Payment against Invoice ' . $invoice->invoice_number,
                'currency' => 'MWK',
                'lines' => [
                    ['product_id' => null, 'description' => "Settlement - {$invoice->invoice_number}", 'quantity' => 1, 'unit_price' => $amount, 'discount' => 0, 'tax_rate' => 0, 'income_account_id' => $accounts['4000'] ?? $revenueId],
                ],
                'payments' => [
                    ['payment_method_id' => $s['pm'], 'amount' => $amount, 'bank_account_id' => null],
                ],
            ], $userId);

            $receiptService->post($receipt, $userId);
        }

        // Deposit the two older undeposited receipts into the Main Bank account.
        $depositService->create(
            $companyId,
            $ids['bank_accounts']['1000'],
            now()->subDays(10)->toDateString(),
            $this->undepositedLineIds($companyId),
            $userId,
            ['post' => true, 'description' => 'Weekly bank deposit - Lilongwe branch', 'reference' => 'DEP-WK1']
        );
    }

    protected function undepositedLineIds(int $companyId): array
    {
        return app(BankingDepositService::class)
            ->undepositedLines($companyId)
            ->pluck('line_id')
            ->sort()
            ->take(30)
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------ undeposited receipts

    /**
     * Seed a few posted, standalone sales receipts whose payments clear to the
     * 1050 Undeposited Funds account and are deliberately NOT claimed by any
     * bank deposit. This gives the redesigned Deposits page ("Undeposited
     * Receipts" card + New Deposit receipt pickers) live data to display.
     *
     * Runs AFTER seedArCycle() so its DEP-WK1 deposit has already claimed the
     * two older AR receipts; the receipts created here remain un-deposited.
     * Idempotent for a given company id (guarded on the UNP-001 reference).
     */
    protected function seedUndepositedReceipts(int $companyId, int $userId, array $accounts, array $ids): void
    {
        if (\App\Models\SalesReceipt::where('company_id', $companyId)->where('reference', 'UNP-001')->exists()) {
            return;
        }

        $receiptService = app(SalesReceiptService::class);
        $customerIds = $ids['customers'];
        $productIds = $ids['products'];
        $branchId = $ids['branch_id'];
        $revenueId = $accounts['4000'] ?? null;

        // Payment methods 0 (Bank Transfer) and 1 (Mobile Money) both clear to
        // 1050; with bank_account_id = null the posting debits 1050.
        $undepositedPm = $ids['payment_methods'][0] ?? null;
        $mobilePm = $ids['payment_methods'][1] ?? null;

        $items = [
            ['customer' => 3, 'product' => 'TB100', 'qty' => 12, 'price' => 4200, 'days_ago' => 4, 'pm' => $undepositedPm],
            ['customer' => 4, 'product' => 'BW500', 'qty' => 20, 'price' => 2600, 'days_ago' => 2, 'pm' => $mobilePm],
            ['customer' => 5, 'product' => 'SVC-CNS', 'qty' => 1, 'price' => 25000, 'days_ago' => 1, 'pm' => $undepositedPm],
        ];

        foreach ($items as $i => $item) {
            $product = Product::find($productIds[$item['product']]);
            $amount = round($item['qty'] * $item['price'], 2);

            $receipt = $receiptService->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customerIds[$item['customer']],
                'invoice_id' => null,
                'receipt_date' => now()->subDays($item['days_ago'])->toDateString(),
                'reference' => 'UNP-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'memo' => 'Walk-in payment - awaiting deposit',
                'currency' => 'MWK',
                'lines' => [[
                    'product_id' => $productIds[$item['product']],
                    'description' => $product->name,
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'discount' => 0,
                    'tax_rate' => 0,
                    'income_account_id' => $revenueId,
                ]],
                'payments' => [
                    ['payment_method_id' => $item['pm'], 'amount' => $amount, 'bank_account_id' => null],
                ],
            ], $userId);

            $receiptService->post($receipt, $userId);
        }
    }

    // ------------------------------------------------------------------ banking ops

    protected function seedBankingOps(int $companyId, int $userId, array $accounts, array $ids): void
    {
        $bankService = app(BankService::class);
        $pettyCash = app(PettyCashService::class);

        // Idempotency guard: skip if the seeded cheque (first cheque on Main)
        // already exists — transfers/petty were then also already created.
        $mainEl = $ids['bank_accounts']['1000'];
        if (\App\Models\Cheque::where('company_id', $companyId)->where('bank_account_id', $mainEl)->where('cheque_number', 1)->exists()) {
            return;
        }

        $main = $ids['bank_accounts']['1000'];
        $savings = $ids['bank_accounts']['1030'];
        $pettyAcct = $ids['bank_accounts']['1010'];
        $expenseId = $accounts['5000'] ?? null;

        // 1. Bank transfer from Main -> Savings.
        $bankService->transfer($main, $savings, 150000, now()->subDays(8)->toDateString(), 'Transfer to savings reserve', $companyId, $userId);

        // 2. Petty cash: establish float, record two small expenses, replenish.
        $pettyCash->establishFund(
            Account::find($pettyAcct),
            $main,
            100000,
            now()->subDays(6)->toDateString(),
            $userId
        );

        $pettyCash->recordExpense([
            'company_id' => $companyId,
            'petty_cash_account_id' => $pettyAcct,
            'debit_account_id' => $expenseId,
            'date' => now()->subDays(5)->toDateString(),
            'amount' => 24000,
            'description' => 'Cleaning supplies & office refreshments',
        ], $userId);

        $pettyCash->recordExpense([
            'company_id' => $companyId,
            'petty_cash_account_id' => $pettyAcct,
            'debit_account_id' => $expenseId,
            'date' => now()->subDays(4)->toDateString(),
            'amount' => 18000,
            'description' => 'Local transport / courier pickups',
        ], $userId);

        $pettyCash->replenishFund([
            'company_id' => $companyId,
            'petty_cash_account_id' => $pettyAcct,
            'bank_account_id' => $main,
            'date' => now()->subDays(3)->toDateString(),
            'amount' => 42000,
            'description' => 'Petty cash top-up',
        ], $userId);

        // 3. Cheque written to a vendor (source of payment).
        $this->writeCheque($companyId, $userId, [
            'bank_account_id' => $main,
            'date' => now()->subDays(2)->toDateString(),
            'payee' => Vendor::find($ids['vendors'][0])->name,
            'amount' => 65000,
            'debit_account_id' => $accounts['2000'] ?? $expenseId,
            'memo' => 'Payment of Bill ',
        ]);
    }

    protected function writeCheque(int $companyId, int $userId, array $data): void
    {
        $postingEngine = app(JournalPostingEngine::class);
        $bankAccount = Account::find($data['bank_account_id']);
        $chequeNumber = $bankAccount->next_cheque_number ?? 1;
        $bankAccount->update(['next_cheque_number' => $chequeNumber + 1]);

        $journalEntry = $postingEngine->post([
            'company_id' => $companyId,
            'created_by' => $userId,
            'date' => $data['date'],
            'source_module' => 'cheque',
            'reference' => 'CHQ-' . str_pad((string) $chequeNumber, 6, '0', STR_PAD_LEFT),
            'memo' => $data['memo'] ?? "Cheque #{$chequeNumber} to {$data['payee']}",
            'lines' => [
                ['account_id' => $data['debit_account_id'], 'debit' => $data['amount'], 'credit' => 0, 'memo' => "Cheque #{$chequeNumber} to {$data['payee']}"],
                ['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => $data['amount'], 'memo' => "Cheque #{$chequeNumber} to {$data['payee']}"],
            ],
        ]);

        $bankTx = \App\Models\BankTransaction::create([
            'company_id' => $companyId,
            'bank_account_id' => $bankAccount->id,
            'journal_entry_id' => $journalEntry->id,
            'type' => 'withdrawal',
            'source_type' => 'cheque',
            'source_id' => $journalEntry->id,
            'date' => $data['date'],
            'description' => "Cheque #{$chequeNumber} to {$data['payee']}",
            'reference' => 'CHQ-' . str_pad((string) $chequeNumber, 6, '0', STR_PAD_LEFT),
            'amount' => -1 * $data['amount'],
            'created_by' => $userId,
        ]);

        \App\Models\Cheque::create([
            'company_id' => $companyId,
            'bank_account_id' => $bankAccount->id,
            'cheque_number' => $chequeNumber,
            'date' => $data['date'],
            'payee' => $data['payee'],
            'memo' => $data['memo'] ?? null,
            'amount' => $data['amount'],
            'status' => \App\Models\Cheque::STATUS_OUTSTANDING,
            'source_type' => 'bank_transaction',
            'source_id' => $bankTx->id,
            'journal_entry_id' => $journalEntry->id,
            'created_by' => $userId,
        ]);
    }

    // ------------------------------------------------------------------ counts

    protected function counts(int $companyId): array
    {
        return [
            'customers' => Customer::where('company_id', $companyId)->count(),
            'vendors' => Vendor::where('company_id', $companyId)->count(),
            'products' => Product::where('company_id', $companyId)->count(),
            'employees' => Employee::where('company_id', $companyId)->count(),
            'accounts' => Account::where('company_id', $companyId)->count(),
            'journal_entries' => \App\Models\JournalEntry::where('company_id', $companyId)->count(),
            'invoices' => \App\Models\Invoice::where('company_id', $companyId)->count(),
            'bills' => \App\Models\Bill::where('company_id', $companyId)->count(),
            'sales_receipts' => \App\Models\SalesReceipt::where('company_id', $companyId)->count(),
            'bank_deposits' => \App\Models\BankDeposit::where('company_id', $companyId)->count(),
            'bank_transactions' => \App\Models\BankTransaction::where('company_id', $companyId)->count(),
            'cheques' => \App\Models\Cheque::where('company_id', $companyId)->count(),
        ];
    }
}

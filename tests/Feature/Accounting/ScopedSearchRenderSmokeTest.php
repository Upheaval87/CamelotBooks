<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopedSearchRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        foreach (['banking', 'fixed_assets', 'inventory', 'payroll', 'pos', 'purchasing'] as $feature) {
            FeatureManagement::enable($this->company->id, $feature);
        }

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    public function test_converted_pages_render(): void
    {
        $bank = Account::create([
            'company_id' => $this->company->id,
            'code' => 'BK01',
            'name' => 'Main Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank' => true,
            'is_bank_account' => true,
        ]);
        $pettyCash = Account::create([
            'company_id' => $this->company->id,
            'code' => 'PC01',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_petty_cash' => true,
        ]);
        $vendor = Vendor::create(['company_id' => $this->company->id, 'name' => 'ACME Corp']);
        $branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Main Branch', 'code' => 'BR01']);
        $costCenter = CostCenter::create(['company_id' => $this->company->id, 'name' => 'Admin', 'code' => 'CC01']);
        $customer = Customer::create(['company_id' => $this->company->id, 'name' => 'Widget Co']);
        $employee = Employee::create(['company_id' => $this->company->id, 'employee_number' => 'EMP001', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'hire_date' => '2024-01-01']);
        $expenseCategory = \App\Models\ExpenseCategory::create(['company_id' => $this->company->id, 'name' => 'Travel', 'is_active' => true, 'created_by' => $this->user->id]);
        $expense = \App\Models\Expense::create([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'category_id' => $expenseCategory->id,
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'expense_number' => 'EXP-0001',
            'expense_date' => '2026-08-01',
            'status' => \App\Models\Expense::STATUS_DRAFT,
            'amount' => 250,
            'subtotal' => 250,
            'tax_total' => 0,
            'created_by' => $this->user->id,
        ]);
        $claim = \App\Models\ExpenseClaim::create([
            'company_id' => $this->company->id,
            'claim_number' => 'CLM-0001',
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'category_id' => $expenseCategory->id,
            'expense_date' => '2026-08-02',
            'amount' => 120,
            'status' => \App\Models\ExpenseClaim::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);
        $recurringTemplate = \App\Models\ExpenseRecurringTemplate::create([
            'company_id' => $this->company->id,
            'name' => 'Internet',
            'category_id' => $expenseCategory->id,
            'vendor_id' => $vendor->id,
            'amount' => 75,
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => '2026-01-01',
            'expense_account_id' => $this->incomeAccount->id,
            'created_by' => $this->user->id,
        ]);
        $depExp = Account::create([
            'company_id' => $this->company->id,
            'code' => '6200',
            'name' => 'Depreciation Expense',
            'type' => 'expense',
            'sub_type' => 'depreciation',
            'is_active' => true,
        ]);
        $category = \App\Models\AssetCategory::create([
            'company_id' => $this->company->id,
            'code' => 'MACH-01',
            'name' => 'Machinery',
            'depreciation_method_financial' => 'straight_line',
            'useful_life_financial' => 60,
            'residual_value_type_financial' => 'amount',
            'residual_value_financial' => 1000,
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_type_tax' => 'amount',
            'residual_value_tax' => 1000,
            'is_active' => true,
            'asset_account_id' => $this->incomeAccount->id,
            'accumulated_depreciation_account_id' => $bank->id,
            'depreciation_expense_account_id' => $depExp->id,
        ]);
        $asset = Asset::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'asset_code' => 'A-1001',
            'name' => 'Laptop',
            'acquisition_date' => '2024-01-01',
            'in_service_date' => '2024-01-01',
            'acquisition_cost' => 1000,
            'useful_life' => 36,
            'depreciation_method_financial' => 'straight_line',
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 36,
            'asset_account_id' => $this->incomeAccount->id,
            'accumulated_depreciation_account_id' => $bank->id,
            'depreciation_expense_account_id' => $depExp->id,
            'is_active' => true,
            'branch_id' => $branch->id,
        ]);
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'run_number' => 'PR-0001',
            'period_label' => 'January 2024',
            'pay_date' => '2024-01-31',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'status' => 'draft',
        ]);

        $itemCategory = \App\Models\ItemCategory::create([
            'company_id' => $this->company->id,
            'code' => 'CAT-01',
            'name' => 'Resale Goods',
            'default_income_account_id' => $this->incomeAccount->id,
            'default_cogs_account_id' => $depExp->id,
            'default_inventory_asset_account_id' => $bank->id,
            'default_base_uom' => 'each',
            'is_active' => true,
        ]);
        $product = \App\Models\Product::create([
            'company_id' => $this->company->id,
            'category_id' => $itemCategory->id,
            'name' => 'Widget 3000',
            'sku' => 'WG-3000',
            'type' => 'product',
            'tracked_as_inventory' => false,
            'sales_price' => 100,
            'unit_of_measure' => 'each',
            'income_account_id' => $this->incomeAccount->id,
            'expense_account_id' => $depExp->id,
            'tax_rate' => 16.5,
            'is_taxable' => true,
            'is_active' => true,
        ]);
        $invoice = \App\Models\Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-0001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => \App\Models\Invoice::STATUS_DRAFT,
            'amount' => 116.50,
            'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
        \App\Models\InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'transaction_uom' => 'each',
            'transaction_qty' => 1,
            'conversion_factor' => 1,
            'description' => 'Widget 3000',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 16.5,
            'amount' => 100,
            'tax_amount' => 16.50,
            'line_total' => 116.50,
            'income_account_id' => $this->incomeAccount->id,
            'cost_center_id' => $costCenter->id,
        ]);
        $quot = \App\Models\Quotation::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'quotation_number' => 'Q-0001',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(14),
            'status' => \App\Models\Quotation::STATUS_SENT,
            'amount' => 116.50,
            'tax_total' => 16.50,
            'total' => 116.50,
            'created_by' => $this->user->id,
        ]);
        $quotDraft = \App\Models\Quotation::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'quotation_number' => 'Q-0002',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(14),
            'status' => \App\Models\Quotation::STATUS_DRAFT,
            'amount' => 116.50,
            'tax_total' => 16.50,
            'total' => 116.50,
            'created_by' => $this->user->id,
        ]);

        $salesOrder = \App\Models\SalesOrder::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'sales_order_number' => 'SO-0001',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(14),
            'status' => \App\Models\SalesOrder::STATUS_SENT,
            'amount' => 116.50,
            'tax_total' => 16.50,
            'total' => 133.00,
            'currency' => 'MWK',
            'created_by' => $this->user->id,
        ]);
        \App\Models\SalesOrderLine::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'description' => 'Widget 3000',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 16.5,
            'amount' => 100,
            'tax_amount' => 16.50,
            'line_total' => 116.50,
            'income_account_id' => $this->incomeAccount->id,
            'cost_center_id' => $costCenter->id,
        ]);
        $salesOrderDraft = \App\Models\SalesOrder::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'sales_order_number' => 'SO-0002',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(14),
            'status' => \App\Models\SalesOrder::STATUS_DRAFT,
            'amount' => 116.50,
            'tax_total' => 16.50,
            'total' => 133.00,
            'currency' => 'MWK',
            'created_by' => $this->user->id,
        ]);
        \App\Models\SalesOrderLine::create([
            'sales_order_id' => $salesOrderDraft->id,
            'product_id' => $product->id,
            'description' => 'Widget 3000',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 16.5,
            'amount' => 100,
            'tax_amount' => 16.50,
            'line_total' => 116.50,
            'income_account_id' => $this->incomeAccount->id,
            'cost_center_id' => $costCenter->id,
        ]);

        $payMethod = \App\Models\PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'clearing_account_id' => $bank->id,
            'settlement_bank_account_id' => $bank->id,
            'requires_reference' => false,
            'is_active' => true,
        ]);
        $srDraft = \App\Models\SalesReceipt::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'receipt_number' => 'RCT-0001',
            'receipt_date' => now(),
            'status' => \App\Models\SalesReceipt::STATUS_DRAFT,
            'subtotal' => 100.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'total' => 100.00,
            'currency' => 'MWK',
            'created_by' => $this->user->id,
        ]);
        \App\Models\SalesReceiptLine::create([
            'sales_receipt_id' => $srDraft->id,
            'product_id' => $product->id,
            'description' => 'Widget 3000',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'amount' => 100,
            'tax_amount' => 0,
            'line_total' => 100,
            'income_account_id' => $this->incomeAccount->id,
            'cost_center_id' => $costCenter->id,
        ]);
        \App\Models\SalesReceiptPayment::create([
            'sales_receipt_id' => $srDraft->id,
            'payment_method_id' => $payMethod->id,
            'amount' => 100,
            'cash_tendered' => 100,
            'change_given' => 0,
            'reference_number' => null,
        ]);
        $srPosted = \App\Models\SalesReceipt::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'receipt_number' => 'RCT-0002',
            'receipt_date' => now(),
            'status' => \App\Models\SalesReceipt::STATUS_POSTED,
            'subtotal' => 100.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'total' => 100.00,
            'currency' => 'MWK',
            'created_by' => $this->user->id,
        ]);
        $bill = \App\Models\Bill::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-0001',
            'bill_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => \App\Models\Bill::STATUS_APPROVED,
            'amount' => 500.00,
            'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
        $invoiceSent = \App\Models\Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-0002',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => \App\Models\Invoice::STATUS_SENT,
            'amount' => 200.00,
            'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
        $custPay = \App\Models\CustomerPayment::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'payment_number' => 'RCP-0001',
            'payment_date' => now(),
            'amount' => 200.00,
            'payment_method' => 'bank_transfer',
            'reference' => 'TX-REF-1',
            'bank_account_id' => $bank->id,
            'created_by' => $this->user->id,
        ]);
        \App\Models\CustomerPaymentAllocation::create([
            'customer_payment_id' => $custPay->id,
            'invoice_id' => $invoiceSent->id,
            'amount' => 200.00,
        ]);
        $vendPay = \App\Models\VendorPayment::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'vendor_id' => $vendor->id,
            'payment_number' => 'PYT-0001',
            'payment_date' => now(),
            'amount' => 500.00,
            'payment_method' => 'bank_transfer',
            'reference' => 'TX-REF-2',
            'bank_account_id' => $bank->id,
            'created_by' => $this->user->id,
        ]);
        \App\Models\VendorPaymentAllocation::create([
            'vendor_payment_id' => $vendPay->id,
            'bill_id' => $bill->id,
            'amount' => 500.00,
        ]);
        $je = \App\Models\JournalEntry::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'journal_number' => 'JE-0001',
            'date' => now(),
            'reference' => 'REF-001',
            'memo' => 'Test journal entry',
            'status' => \App\Models\JournalEntry::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $bank->id,
            'branch_id' => $branch->id,
            'debit' => 100,
            'credit' => 0,
            'memo' => 'Debit side',
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $this->incomeAccount->id,
            'branch_id' => $branch->id,
            'debit' => 0,
            'credit' => 100,
            'memo' => 'Credit side',
        ]);
        $jePending = \App\Models\JournalEntry::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'journal_number' => 'JE-0002',
            'date' => now(),
            'reference' => 'REF-002',
            'memo' => 'Pending approval entry',
            'status' => \App\Models\JournalEntry::STATUS_PENDING_APPROVAL,
            'created_by' => $this->user->id,
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $jePending->id,
            'account_id' => $bank->id,
            'branch_id' => $branch->id,
            'debit' => 50,
            'credit' => 0,
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $jePending->id,
            'account_id' => $this->incomeAccount->id,
            'branch_id' => $branch->id,
            'debit' => 0,
            'credit' => 50,
        ]);

        $po = \App\Models\PurchaseOrder::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-2026-0001',
            'date' => now(),
            'expected_delivery_date' => now()->addDays(14),
            'status' => \App\Models\PurchaseOrder::STATUS_DRAFT,
            'memo' => 'Test purchase order',
            'created_by' => $this->user->id,
        ]);
        \App\Models\PurchaseOrderLine::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'description' => 'Widget 3000',
            'quantity' => 2,
            'unit_price' => 50,
            'amount' => 100,
            'expense_account_id' => $depExp->id,
            'cost_center_id' => $costCenter->id,
        ]);

        \App\Models\BankTransaction::create([
            'company_id' => $this->company->id,
            'bank_account_id' => $bank->id,
            'journal_entry_id' => $je->id,
            'type' => 'deposit',
            'source_type' => 'journal',
            'source_id' => $je->id,
            'date' => now()->toDateString(),
            'description' => 'Opening deposit',
            'reference' => 'DEP-001',
            'amount' => 100.00,
            'is_reconciled' => false,
            'created_by' => $this->user->id,
        ]);
        $prDraft = \App\Models\PurchaseRequisition::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'requisition_number' => 'REQ-2026-0001',
            'date' => now(),
            'status' => \App\Models\PurchaseRequisition::STATUS_DRAFT,
            'priority' => 'normal',
            'requested_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        \App\Models\PurchaseRequisitionLine::create([
            'purchase_requisition_id' => $prDraft->id,
            'product_id' => $product->id,
            'description' => 'Widget 3000 stock',
            'quantity' => 2,
            'estimated_unit_cost' => 50,
            'estimated_total' => 100,
            'expense_account_id' => $depExp->id,
            'cost_center_id' => $costCenter->id,
        ]);
        $prSubmitted = \App\Models\PurchaseRequisition::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'requisition_number' => 'REQ-2026-0002',
            'date' => now(),
            'status' => \App\Models\PurchaseRequisition::STATUS_SUBMITTED,
            'priority' => 'urgent',
            'required_by' => now()->addDays(7),
            'requested_by' => $this->user->id,
            'submitted_at' => now(),
            'created_by' => $this->user->id,
        ]);
        \App\Models\PurchaseRequisitionLine::create([
            'purchase_requisition_id' => $prSubmitted->id,
            'product_id' => $product->id,
            'description' => 'Widget 3000 urgent',
            'quantity' => 3,
            'estimated_unit_cost' => 60,
            'estimated_total' => 180,
            'expense_account_id' => $depExp->id,
            'cost_center_id' => $costCenter->id,
        ]);

        $routes = [
            'purchase-requisitions.index' => route('accounting.purchase-requisitions.index'),
            'purchase-requisitions.create' => route('accounting.purchase-requisitions.create'),
            'purchase-requisitions.edit' => route('accounting.purchase-requisitions.edit', $prDraft),
            'purchase-requisitions.show' => route('accounting.purchase-requisitions.show', $prSubmitted),
            'bills.create' => route('accounting.bills.create'),
            'expenses.create' => route('accounting.expenses.create'),
            'expenses.index' => route('accounting.expenses.index'),
            'expenses.dashboard' => route('accounting.expenses.dashboard'),
            'expenses.show' => route('accounting.expenses.show', $expense),
            'expenses.edit' => route('accounting.expenses.edit', $expense),
            'expenses.claims.index' => route('accounting.expenses.claims.index'),
            'expenses.claims.create' => route('accounting.expenses.claims.create'),
            'expenses.claims.show' => route('accounting.expenses.claims.show', $claim),
            'expenses.recurring.index' => route('accounting.expenses.recurring.index'),
            'expenses.recurring.create' => route('accounting.expenses.recurring.create'),
            'expenses.categories.index' => route('accounting.expenses.categories.index'),
            'expenses.reports' => route('accounting.expenses.reports'),
            'general-ledger.index' => route('accounting.general-ledger.index'),
            'general-ledger.account' => route('accounting.general-ledger.account', $bank->id),
            'cheques.create' => route('accounting.cheques.create'),
            'cheques.index' => route('accounting.cheques.index'),
            'cheques.register' => route('accounting.cheques.register'),
            'settlements.create' => route('pos.settlements.create'),
            'deposits.create' => route('accounting.deposits.create'),
            'customer-payments.create' => route('accounting.customer-payments.create'),
            'vendor-payments.create' => route('accounting.vendor-payments.create'),
            'vendor-payments.index' => route('accounting.vendor-payments.index'),
            'customer-payments.show' => route('accounting.customer-payments.show', $custPay),
            'vendor-payments.show' => route('accounting.vendor-payments.show', $vendPay),
            'fixed-assets.create' => route('accounting.fixed-assets.create'),
            'goods-received-notes.create' => route('accounting.goods-received-notes.create'),
            'landed-costs.create' => route('accounting.landed-costs.create'),
            'vendor-credits.create' => route('accounting.vendor-credits.create'),
            'purchase-orders.create' => route('accounting.purchase-orders.create'),
            'purchase-orders.index' => route('accounting.purchase-orders.index'),
            'purchase-orders.edit' => route('accounting.purchase-orders.edit', $po),
            'purchase-orders.show' => route('accounting.purchase-orders.show', $po),
            'asset-usage.index' => route('accounting.asset-usage.index'),
            'asset-transfers.create' => route('accounting.asset-transfers.create'),
            'asset-revaluations.create' => route('accounting.asset-revaluations.create'),
            'asset-impairments.create' => route('accounting.asset-impairments.create'),
            'asset-disposals.create' => route('accounting.asset-disposals.create'),
            'customer-statement' => route('accounting.reports.customer-statement'),
            'vendor-statement' => route('accounting.reports.vendor-statement'),
            'payslip-report' => route('accounting.reports.payslip-report'),
            'stock-movement' => route('accounting.reports.stock-movement'),
            'accounts.index' => route('accounting.accounts.index'),
            'credit-notes.index' => route('accounting.credit-notes.index'),
            'quotations.index' => route('accounting.quotations.index'),
            'quotations.create' => route('accounting.quotations.create'),
            'quotations.edit' => route('accounting.quotations.edit', $quotDraft),
            'quotations.show' => route('accounting.quotations.show', $quot),
            'quotations.print' => route('accounting.quotations.print', $quot),
            'sales-orders.index' => route('accounting.sales-orders.index'),
            'sales-orders.create' => route('accounting.sales-orders.create'),
            'sales-orders.edit' => route('accounting.sales-orders.edit', $salesOrderDraft),
            'sales-orders.show' => route('accounting.sales-orders.show', $salesOrder),
            'sales-orders.print' => route('accounting.sales-orders.print', $salesOrder),
            'vendor-credits.index' => route('accounting.vendor-credits.index'),
            'audit-log.index' => route('admin.audit-log.index'),
            'inventory-items.index' => route('accounting.inventory-items.index'),
            'inventory-items.show' => route('accounting.inventory-items.show', $product),
            'inventory-items.print' => route('accounting.inventory-items.print', $product),
            'report-center.index' => route('accounting.report-center.index'),
            'payroll-runs.show' => route('accounting.payroll-runs.show', $run),
            'petty-cash.show' => route('accounting.petty-cash.show', $pettyCash->id),
            'customers.index' => route('accounting.customers.index'),
            'customers.create' => route('accounting.customers.create'),
            'customers.edit' => route('accounting.customers.edit', $customer),
            'customers.show' => route('accounting.customers.show', $customer),
            'vendors.index' => route('accounting.vendors.index'),
            'vendors.create' => route('accounting.vendors.create'),
            'vendors.edit' => route('accounting.vendors.edit', $vendor),
            'vendors.show' => route('accounting.vendors.show', $vendor),
            'invoices.create' => route('accounting.invoices.create'),
            'invoices.create-copy-quote' => route('accounting.invoices.create', ['copy_quote' => $quot->id]),
            'invoices.create-preselect-customer' => route('accounting.invoices.create', ['customer_id' => $customer->id]),
            'invoices.edit' => route('accounting.invoices.edit', $invoice),
            'invoices.show' => route('accounting.invoices.show', $invoice),
            'invoices.print' => route('accounting.invoices.print', $invoice),
            'invoices.copy-quote' => route('accounting.invoices.copy-quote', ['quotation' => $quot->id]),
            'sales-receipts.index' => route('accounting.sales-receipts.index'),
            'sales-receipts.create' => route('accounting.sales-receipts.create'),
            'sales-receipts.edit' => route('accounting.sales-receipts.edit', $srDraft),
            'sales-receipts.show' => route('accounting.sales-receipts.show', $srDraft),
            'sales-receipts.post-page' => route('accounting.sales-receipts.post-page', $srDraft),
            'sales-receipts.print' => route('accounting.sales-receipts.print', $srPosted),
            'reports.sales-receipts.daily-summary' => route('accounting.reports.sales-receipts.daily-summary'),
            'reports.sales-receipts.cashbook' => route('accounting.reports.sales-receipts.cashbook'),
            'journal-entries.index' => route('accounting.journal-entries.index'),
            'journal-entries.create' => route('accounting.journal-entries.create'),
            'journal-entries.show' => route('accounting.journal-entries.show', $je),
            'journal-entries.show-pending' => route('accounting.journal-entries.show', $jePending),
            'bank-accounts.index' => route('accounting.bank-accounts.index'),
            'bank-accounts.register' => route('accounting.bank-accounts.register', $bank->id),
            'bank-accounts.transfer-form' => route('accounting.bank-accounts.transfer-form'),
            'bank-accounts.manual-form' => route('accounting.bank-accounts.manual-form', $bank->id),
            'system-settings.company' => route('system-settings.index', 'company'),
            'system-settings.regional' => route('system-settings.index', 'regional'),
            'system-settings.currency' => route('system-settings.index', 'currency'),
            'system-settings.accounts' => route('system-settings.index', 'accounts'),
            'system-settings.accounting' => route('system-settings.index', 'accounting'),
            'system-settings.approval' => route('system-settings.index', 'approval'),
            'system-settings.notifications' => route('system-settings.index', 'notifications'),
            'system-settings.data-hub' => route('system-settings.index', 'data-hub'),
            'system-settings.import-export' => route('system-settings.index', 'import-export'),
            'system-settings.features' => route('system-settings.features'),
            'system-settings.audit-log' => route('system-settings.audit-log'),
            'admin.backups.index' => route('admin.backups.index'),
        ];

        foreach ($routes as $name => $url) {
            $response = $this->actingAs($this->user)->get($url);
            if ($response->status() !== 200) {
                $ex = $response->exception ? get_class($response->exception) . ': ' . $response->exception->getMessage() : 'no-exception';
                $this->fail("{$name} returned {$response->status()} for {$url} -> " . ($response->headers->get('location') ?? $ex));
            }
        }

        $this->assertCount(count($routes), $routes);
    }
}

<?php

use App\Http\Controllers\Accounting\AccountClassificationController;
use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AccountingPeriodController;
use App\Http\Controllers\Accounting\AgingReportController;
use App\Http\Controllers\Accounting\AssemblyController;
use App\Http\Controllers\Accounting\BalanceSheetController;
use App\Http\Controllers\Accounting\EquityStatementController;
use App\Http\Controllers\Accounting\BankingCentreController;
use App\Http\Controllers\Accounting\BankingAccountController;
use App\Http\Controllers\Accounting\BankingRegisterController;
use App\Http\Controllers\Accounting\BankingTransferController;
use App\Http\Controllers\Accounting\BankingDepositController;
use App\Http\Controllers\Accounting\BankingChequeController;
use App\Http\Controllers\Accounting\BankingPettyCashController;
use App\Http\Controllers\Accounting\BankingReportsController;
use App\Http\Controllers\Accounting\BankReconciliationController;
use App\Http\Controllers\Accounting\BillController;
use App\Http\Controllers\Accounting\BudgetController;
use App\Http\Controllers\Accounting\CashFlowController;
use App\Http\Controllers\Accounting\CreditNoteController;
use App\Http\Controllers\Accounting\CostCenterController;
use App\Http\Controllers\Accounting\CustomerController;
use App\Http\Controllers\Accounting\CustomerPaymentController;
use App\Http\Controllers\Accounting\EmployeeController;
use App\Http\Controllers\Accounting\GeneralLedgerController;
use App\Http\Controllers\Accounting\FiscalYearController;
use App\Http\Controllers\Accounting\GlobalSearchController;
use App\Http\Controllers\Accounting\ExchangeRateController;
use App\Http\Controllers\Accounting\IncomeStatementController;
use App\Http\Controllers\Accounting\InventoryItemsController;
use App\Http\Controllers\Accounting\InventoryValuationController;
use App\Http\Controllers\Accounting\InvoiceController;
use App\Http\Controllers\Accounting\ItemCategoryController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\LowStockController;
use App\Http\Controllers\Accounting\PayrollRunController;
use App\Http\Controllers\Accounting\ProductController;
use App\Http\Controllers\Accounting\RecurringJournalController;
use App\Http\Controllers\Accounting\StockAdjustmentController;
use App\Http\Controllers\Accounting\StockCountController;
use App\Http\Controllers\Accounting\StockTransferController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\VendorController;
use App\Http\Controllers\Accounting\VendorCreditController;
use App\Http\Controllers\Accounting\VendorPaymentController;
use App\Http\Controllers\Accounting\PurchaseRequisitionController;
use App\Http\Controllers\Accounting\PurchaseOrderController;
use App\Http\Controllers\Accounting\GoodsReceivedNoteController;
use App\Http\Controllers\Accounting\QuotationController;
use App\Http\Controllers\Accounting\SalesOrderController;
use App\Http\Controllers\Accounting\ReportCenterController;
use App\Http\Controllers\Accounting\SalesReceiptController;
use App\Http\Controllers\Accounting\SalesRegisterController;

use App\Http\Controllers\Accounting\CashPositionController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchPaymentController;
use App\Http\Controllers\BranchRequestController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavouritesController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\AssignmentsController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\BranchRequestsController;
use App\Http\Controllers\SuperAdmin\CompaniesController;
use App\Http\Controllers\SuperAdmin\CurrenciesController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\UsersController;
use App\Http\Controllers\TodoTaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/companies', [CompanyController::class, 'index'])
        ->name('companies.index');

    Route::post('/companies', [CompanyController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('companies.store');

    Route::post('/companies/{id}/select', [CompanyController::class, 'select'])
        ->middleware('throttle:20,1')
        ->name('companies.select');

    // Super-admin panel (Phase 4 placeholder). Outside the tenant group: a super
    // admin browsing here has NO tenant connection bound unless they explicitly
    // enter a company (logged as support access).
    Route::get('/panel', [\App\Http\Controllers\PanelController::class, 'index'])
        ->name('panel.dashboard');

    // Super Admin panel (Phase 4). Guarded by the central is_super_admin flag via
    // the 'superadmin' middleware. Outside the tenant group: no tenant connection
    // is ever bound while browsing here.
    Route::middleware(['superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/companies', [CompaniesController::class, 'index'])
            ->name('companies.index');
        Route::get('/companies/create', [CompaniesController::class, 'create'])
            ->name('companies.create');
        Route::post('/companies', [CompaniesController::class, 'store'])
            ->name('companies.store');
        Route::get('/companies/{company}', [CompaniesController::class, 'show'])
            ->name('companies.show');
        Route::get('/companies/{company}/edit', [CompaniesController::class, 'edit'])
            ->name('companies.edit');
        Route::patch('/companies/{company}', [CompaniesController::class, 'update'])
            ->name('companies.update');
        Route::post('/companies/{company}/suspend', [CompaniesController::class, 'suspend'])
            ->name('companies.suspend');
        Route::post('/companies/{company}/reactivate', [CompaniesController::class, 'reactivate'])
            ->name('companies.reactivate');
        Route::get('/companies/{company}/modules', [CompaniesController::class, 'modules'])
            ->name('companies.modules');
        Route::post('/companies/{company}/modules/{module}/toggle', [CompaniesController::class, 'toggleModule'])
            ->name('companies.modules.toggle');
        Route::get('/companies/{company}/branches', [CompaniesController::class, 'branches'])
            ->name('companies.branches');
        Route::patch('/companies/{company}/branch-limit', [CompaniesController::class, 'updateBranchLimit'])
            ->name('companies.branch-limit');

        Route::get('/branch-requests', [BranchRequestsController::class, 'index'])
            ->name('branch-requests.index');
        Route::get('/companies/{company}/branch-requests/{branchRequest}', [BranchRequestsController::class, 'show'])
            ->name('companies.branch-requests.show');
        Route::post('/companies/{company}/branch-requests/{branchRequest}/approve', [BranchRequestsController::class, 'approve'])
            ->name('companies.branch-requests.approve');
        Route::post('/companies/{company}/branch-requests/{branchRequest}/reject', [BranchRequestsController::class, 'reject'])
            ->name('companies.branch-requests.reject');

        Route::get('/users', [UsersController::class, 'index'])
            ->name('users.index');
        Route::get('/users/create', [UsersController::class, 'create'])
            ->name('users.create');
        Route::post('/users', [UsersController::class, 'store'])
            ->name('users.store');
        Route::get('/users/{user}', [UsersController::class, 'show'])
            ->name('users.show');
        Route::get('/users/{user}/edit', [UsersController::class, 'edit'])
            ->name('users.edit');
        Route::patch('/users/{user}', [UsersController::class, 'update'])
            ->name('users.update');
        Route::post('/users/{user}/deactivate', [UsersController::class, 'deactivate'])
            ->name('users.deactivate');
        Route::post('/users/{user}/reactivate', [UsersController::class, 'reactivate'])
            ->name('users.reactivate');
        Route::post('/users/{user}/reset-password', [UsersController::class, 'resetPassword'])
            ->name('users.reset-password');

        Route::get('/assignments', [AssignmentsController::class, 'index'])
            ->name('assignments.index');
        Route::get('/assignments/create', [AssignmentsController::class, 'create'])
            ->name('assignments.create');
        Route::post('/assignments', [AssignmentsController::class, 'store'])
            ->name('assignments.store');
        Route::get('/assignments/{assignment}/edit', [AssignmentsController::class, 'edit'])
            ->name('assignments.edit');
        Route::patch('/assignments/{assignment}', [AssignmentsController::class, 'update'])
            ->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentsController::class, 'destroy'])
            ->name('assignments.destroy');

        Route::get('/audit-log', [AuditLogController::class, 'index'])
            ->name('audit.index');

        Route::get('/currencies', [CurrenciesController::class, 'index'])
            ->name('currencies.index');
        Route::get('/currencies/create', [CurrenciesController::class, 'create'])
            ->name('currencies.create');
        Route::post('/currencies', [CurrenciesController::class, 'store'])
            ->name('currencies.store');
        Route::get('/currencies/{currency}/edit', [CurrenciesController::class, 'edit'])
            ->name('currencies.edit');
        Route::patch('/currencies/{currency}', [CurrenciesController::class, 'update'])
            ->name('currencies.update');
        Route::patch('/currencies/{currency}/toggle', [CurrenciesController::class, 'toggle'])
            ->name('currencies.toggle');

        Route::get('/db-preview', [CompaniesController::class, 'dbPreview'])
            ->name('db-preview');
    });

    Route::middleware(['tenant.bind', 'company.context', 'company.active'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::patch('/companies/{company}', [CompanyController::class, 'update'])
            ->name('companies.update');

        Route::get('/branches', [BranchController::class, 'index'])
            ->name('branches.index');

        Route::get('/branches/usage', [BranchController::class, 'usage'])
            ->name('branches.usage');

        Route::post('/branches', [BranchController::class, 'store'])
            ->middleware('role_or_permission:system_admin|company_admin')
            ->name('branches.store');

        Route::patch('/branches/{branch}', [BranchController::class, 'update'])
            ->middleware('role_or_permission:system_admin|company_admin')
            ->name('branches.update');

        Route::patch('/branches/{branch}/toggle', [BranchController::class, 'toggle'])
            ->middleware('role_or_permission:system_admin|company_admin')
            ->name('branches.toggle');

        Route::get('/branch-requests', [BranchRequestController::class, 'index'])
            ->name('branch-requests.index');

        Route::post('/branch-requests', [BranchRequestController::class, 'store'])
            ->middleware('role_or_permission:system_admin|company_admin')
            ->name('branch-requests.store');

        Route::get('/branch-requests/{branchRequest}', [BranchRequestController::class, 'show'])
            ->name('branch-requests.show');

        Route::post('/branch-requests/{branchRequest}/cancel', [BranchRequestController::class, 'cancel'])
            ->middleware('role_or_permission:system_admin|company_admin')
            ->name('branch-requests.cancel');

        Route::post('/branch-requests/{branchRequest}/payments', [BranchPaymentController::class, 'store'])
            ->middleware('role_or_permission:system_admin|company_admin|accountant|billing')
            ->name('branch-requests.payments.store');

        Route::post('/branch-requests/{branchRequest}/payments/{payment}/confirm', [BranchPaymentController::class, 'confirm'])
            ->middleware('role_or_permission:system_admin|accountant|billing')
            ->name('branch-requests.payments.confirm');

        Route::post('/branch-requests/{branchRequest}/payments/{payment}/reject', [BranchPaymentController::class, 'reject'])
            ->middleware('role_or_permission:system_admin|accountant|billing')
            ->name('branch-requests.payments.reject');

        Route::prefix('accounting')->name('accounting.')
            ->middleware('role_or_permission:system_admin|company_admin|accountant|approver|viewer')
            ->group(function () {
            // Scoped search (Mode 1) — per entity type
            Route::get('search/{entity}', [GlobalSearchController::class, 'entity'])
                ->name('search.entity')
                ->where('entity', 'product|account|customer|vendor|branch|cost-center|employee|user|bank-account|asset|asset-category|payroll-run|fiscal-year');

            // Chart of Accounts
            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('accounts/create', [AccountController::class, 'create'])->name('accounts.create');
            Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
            Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
            Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
            Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
            Route::patch('accounts/{account}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');

            // Journal Entries
            Route::get('journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
            Route::get('journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
            Route::post('journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
            Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
            Route::post('journal-entries/{journalEntry}/submit-for-approval', [JournalEntryController::class, 'submitForApproval'])->name('journal-entries.submit-for-approval')->middleware(['permission:journal-entries.edit', 'sod:journalEntry']);
            Route::post('journal-entries/{journalEntry}/approve', [JournalEntryController::class, 'approve'])->name('journal-entries.approve')->middleware(['permission:journal-entries.approve', 'sod:journalEntry']);
            Route::post('journal-entries/{journalEntry}/reject', [JournalEntryController::class, 'reject'])->name('journal-entries.reject')->middleware(['permission:journal-entries.approve', 'sod:journalEntry']);
            Route::post('journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entries.reverse')->middleware(['permission:journal-entries.reverse', 'sod:journalEntry']);

            // General Ledger
            Route::get('general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger.index');
            Route::get('general-ledger/export/csv', [GeneralLedgerController::class, 'exportCsv'])->name('general-ledger.export-csv');
            Route::get('general-ledger/{accountId}', [GeneralLedgerController::class, 'account'])->name('general-ledger.account');
            Route::get('general-ledger/{accountId}/export/csv', [GeneralLedgerController::class, 'exportCsv'])->name('general-ledger.account-export-csv');
            Route::get('general-ledger/{accountId}/export/pdf', [GeneralLedgerController::class, 'exportPdf'])->name('general-ledger.account-export-pdf');

            // Trial Balance
            Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance.index');
            Route::get('trial-balance/export/csv', [TrialBalanceController::class, 'exportCsv'])->name('trial-balance.export-csv');
            Route::get('trial-balance/export/pdf', [TrialBalanceController::class, 'exportPdf'])->name('trial-balance.export-pdf');

            // Accounting Periods
            Route::get('periods', [AccountingPeriodController::class, 'index'])->name('periods.index');
            Route::post('periods', [AccountingPeriodController::class, 'store'])->name('periods.store');
            Route::post('periods/{period}/close', [AccountingPeriodController::class, 'close'])->name('periods.close')->middleware('sod:period');
            Route::post('periods/{period}/lock', [AccountingPeriodController::class, 'lock'])->name('periods.lock')->middleware('sod:period');
            Route::post('periods/{period}/reopen', [AccountingPeriodController::class, 'reopen'])->name('periods.reopen')->middleware('sod:period');

            // Fiscal Years
            Route::get('fiscal-years', [FiscalYearController::class, 'index'])->name('fiscal-years.index');
            Route::post('fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
            Route::get('fiscal-years/{fiscalYear}', [FiscalYearController::class, 'show'])->name('fiscal-years.show');
            Route::post('fiscal-years/{fiscalYear}/close', [FiscalYearController::class, 'close'])->name('fiscal-years.close')->middleware('sod:fiscalYear');
            Route::patch('fiscal-years/{fiscalYear}/reopen', [FiscalYearController::class, 'reopen'])->name('fiscal-years.reopen')->middleware('sod:fiscalYear');

            // Cost Centers
            Route::get('cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
            Route::post('cost-centers', [CostCenterController::class, 'store'])->name('cost-centers.store');
            Route::patch('cost-centers/{costCenter}', [CostCenterController::class, 'update'])->name('cost-centers.update');
            Route::patch('cost-centers/{costCenter}/toggle', [CostCenterController::class, 'toggle'])->name('cost-centers.toggle');

            // Exchange Rates
            Route::get('exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
            Route::post('exchange-rates', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');
            Route::delete('exchange-rates/{exchangeRate}', [ExchangeRateController::class, 'destroy'])->name('exchange-rates.destroy');
            Route::post('exchange-rates/bulk', [ExchangeRateController::class, 'bulkStore'])->name('exchange-rates.bulk');

            // Recurring Journals
            Route::get('recurring-journals', [RecurringJournalController::class, 'index'])->name('recurring-journals.index');
            Route::get('recurring-journals/create', [RecurringJournalController::class, 'create'])->name('recurring-journals.create');
            Route::post('recurring-journals', [RecurringJournalController::class, 'store'])->name('recurring-journals.store');
            Route::get('recurring-journals/{template}', [RecurringJournalController::class, 'show'])->name('recurring-journals.show');
            Route::get('recurring-journals/{template}/edit', [RecurringJournalController::class, 'edit'])->name('recurring-journals.edit');
            Route::put('recurring-journals/{template}', [RecurringJournalController::class, 'update'])->name('recurring-journals.update');
            Route::patch('recurring-journals/{template}/toggle', [RecurringJournalController::class, 'toggle'])->name('recurring-journals.toggle');

            // Customers
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
            Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
            Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::patch('customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');

            // Vendors
            Route::get('vendors/dashboard', [VendorController::class, 'dashboard'])->name('vendors.dashboard');
            Route::get('vendors/reports', [VendorController::class, 'reports'])->name('vendors.reports');
            Route::get('vendors/settings', [VendorController::class, 'settings'])->name('vendors.settings');
            Route::post('vendors/settings', [VendorController::class, 'updateSettings'])->name('vendors.settings.update');
            Route::get('vendors/export', [VendorController::class, 'exportCsv'])->name('vendors.export');
            Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
            Route::get('vendors/create', [VendorController::class, 'create'])->name('vendors.create');
            Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
            Route::get('vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
            Route::get('vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
            Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
            Route::patch('vendors/{vendor}/toggle', [VendorController::class, 'toggle'])->name('vendors.toggle');

            // Item Categories
            Route::get('item-categories', [ItemCategoryController::class, 'index'])->name('item-categories.index');
            Route::get('item-categories/create', [ItemCategoryController::class, 'create'])->name('item-categories.create');
            Route::post('item-categories', [ItemCategoryController::class, 'store'])->name('item-categories.store');
            Route::get('item-categories/{category}', [ItemCategoryController::class, 'show'])->name('item-categories.show');
            Route::get('item-categories/{category}/edit', [ItemCategoryController::class, 'edit'])->name('item-categories.edit');
            Route::put('item-categories/{category}', [ItemCategoryController::class, 'update'])->name('item-categories.update');
            Route::patch('item-categories/{category}/toggle', [ItemCategoryController::class, 'toggle'])->name('item-categories.toggle');

            // Products
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
            Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');

            Route::middleware('feature:inventory')->group(function () {
            // Inventory Items
                Route::get('inventory-items', [InventoryItemsController::class, 'index'])->name('inventory-items.index');
                Route::get('inventory-items/{product}/print', [InventoryItemsController::class, 'print'])->name('inventory-items.print');
                Route::get('inventory-items/{product}', [InventoryItemsController::class, 'show'])->name('inventory-items.show');

            // Stock Adjustments
                Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
                Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
                Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
                Route::get('stock-adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');

            // Stock Transfers
                Route::get('stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
                Route::get('stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
                Route::post('stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
                Route::get('stock-transfers/{transfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');

            // Assemblies
                Route::get('assemblies', [AssemblyController::class, 'index'])->name('assemblies.index');
                Route::get('assemblies/create', [AssemblyController::class, 'create'])->name('assemblies.create');
                Route::post('assemblies', [AssemblyController::class, 'store'])->name('assemblies.store');
                Route::get('assemblies/unbuild', [AssemblyController::class, 'createUnbuild'])->name('assemblies.unbuild-form');
                Route::post('assemblies/unbuild', [AssemblyController::class, 'storeUnbuild'])->name('assemblies.store-unbuild');
                Route::get('assemblies/history', [AssemblyController::class, 'history'])->name('assemblies.history');
                Route::get('assemblies/{build}', [AssemblyController::class, 'show'])->name('assemblies.show');
                Route::get('assemblies-boms', [AssemblyController::class, 'boms'])->name('assemblies.boms');
                Route::get('assemblies-boms/create', [AssemblyController::class, 'createBom'])->name('assemblies.create-bom');
                Route::post('assemblies-boms', [AssemblyController::class, 'storeBom'])->name('assemblies.store-bom');

            // Stock Counts
                Route::get('stock-counts', [StockCountController::class, 'index'])->name('stock-counts.index');
                Route::get('stock-counts/create', [StockCountController::class, 'create'])->name('stock-counts.create');
                Route::post('stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
                Route::get('stock-counts/{count}', [StockCountController::class, 'show'])->name('stock-counts.show');
                Route::get('stock-counts/{count}/edit', [StockCountController::class, 'edit'])->name('stock-counts.edit');
                Route::put('stock-counts/{count}', [StockCountController::class, 'update'])->name('stock-counts.update');

            // Inventory Valuation
                Route::get('inventory-valuation', [InventoryValuationController::class, 'index'])->name('inventory-valuation.index');
                Route::get('inventory-valuation/by-category', [InventoryValuationController::class, 'byCategory'])->name('inventory-valuation.by-category');
                Route::get('inventory-valuation/export/csv', [InventoryValuationController::class, 'exportCsv'])->name('inventory-valuation.export-csv');
                Route::get('inventory-valuation/export/pdf', [InventoryValuationController::class, 'exportPdf'])->name('inventory-valuation.export-pdf');

            // Low Stock Report
                Route::get('low-stock', [LowStockController::class, 'index'])->name('low-stock.index');
                Route::get('low-stock/export/csv', [LowStockController::class, 'exportCsv'])->name('low-stock.export-csv');
            });

            // Employees
            Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::patch('employees/{employee}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');

            Route::middleware('feature:payroll')->group(function () {
            // Payroll Runs
                Route::get('payroll-runs', [PayrollRunController::class, 'index'])->name('payroll-runs.index');
                Route::get('payroll-runs/create', [PayrollRunController::class, 'create'])->name('payroll-runs.create');
                Route::post('payroll-runs', [PayrollRunController::class, 'store'])->name('payroll-runs.store');
                Route::get('payroll-runs/{run}', [PayrollRunController::class, 'show'])->name('payroll-runs.show');
                Route::post('payroll-runs/{run}/approve', [PayrollRunController::class, 'approve'])->name('payroll-runs.approve')->middleware('sod:run');
                Route::post('payroll-runs/{run}/post', [PayrollRunController::class, 'post'])->name('payroll-runs.post')->middleware('sod:run');
                Route::post('payroll-runs/{run}/send-payslips', [PayrollRunController::class, 'sendPayslips'])->name('payroll-runs.send-payslips');
                Route::post('payroll-runs/{run}/pay-employee/{employeeId}', [PayrollRunController::class, 'payEmployee'])->name('payroll-runs.pay-employee');
                Route::post('payroll-runs/{run}/remit-paye', [PayrollRunController::class, 'remitPaye'])->name('payroll-runs.remit-paye');
                Route::post('payroll-runs/{run}/remit-pension', [PayrollRunController::class, 'remitPension'])->name('payroll-runs.remit-pension');
                Route::get('payroll-runs/{run}/payslip/{itemId}', [PayrollRunController::class, 'payslip'])->name('payroll-runs.payslip');
                Route::get('payroll-runs/{run}/payslips', [PayrollRunController::class, 'payslips'])->name('payroll-runs.payslips');
                Route::get('payroll-runs/{run}/paye-schedule', [PayrollRunController::class, 'payeRemittanceSchedule'])->name('payroll-runs.paye-schedule');
                Route::get('payroll-runs/{run}/pension-schedule', [PayrollRunController::class, 'pensionRemittanceSchedule'])->name('payroll-runs.pension-schedule');
                Route::get('paye-tables', [\App\Http\Controllers\Accounting\PayeTableController::class, 'index'])->name('paye-tables.index');
                Route::get('paye-tables/create', [\App\Http\Controllers\Accounting\PayeTableController::class, 'create'])->name('paye-tables.create');
                Route::post('paye-tables', [\App\Http\Controllers\Accounting\PayeTableController::class, 'store'])->name('paye-tables.store');
                Route::get('paye-tables/{payeTable}', [\App\Http\Controllers\Accounting\PayeTableController::class, 'show'])->name('paye-tables.show');
                Route::get('paye-tables/{payeTable}/edit', [\App\Http\Controllers\Accounting\PayeTableController::class, 'edit'])->name('paye-tables.edit');
                Route::patch('paye-tables/{payeTable}', [\App\Http\Controllers\Accounting\PayeTableController::class, 'update'])->name('paye-tables.update');
                Route::post('paye-tables/{payeTable}/activate', [\App\Http\Controllers\Accounting\PayeTableController::class, 'activate'])->name('paye-tables.activate');
                Route::delete('paye-tables/{payeTable}', [\App\Http\Controllers\Accounting\PayeTableController::class, 'destroy'])->name('paye-tables.destroy');

            // Pension Schemes
                Route::get('pension-schemes', [\App\Http\Controllers\Accounting\PensionSchemeController::class, 'index'])->name('pension-schemes.index');
                Route::get('pension-schemes/create', [\App\Http\Controllers\Accounting\PensionSchemeController::class, 'create'])->name('pension-schemes.create');
                Route::post('pension-schemes', [\App\Http\Controllers\Accounting\PensionSchemeController::class, 'store'])->name('pension-schemes.store');
                Route::get('pension-schemes/{scheme}', [\App\Http\Controllers\Accounting\PensionSchemeController::class, 'show'])->name('pension-schemes.show');
                Route::get('pension-schemes/{scheme}/edit', [\App\Http\Controllers\Accounting\PensionSchemeController::class, 'edit'])->name('pension-schemes.edit');
                Route::put('pension-schemes/{scheme}', [\App\Http\Controllers\Accounting\PensionSchemeController::class, 'update'])->name('pension-schemes.update');
                Route::patch('pension-schemes/{scheme}/toggle', [\App\Http\Controllers\Accounting\PensionSchemeController::class, 'toggle'])->name('pension-schemes.toggle');
            });

            // Sales Invoices
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::get('invoices/copy-from-quote', [InvoiceController::class, 'copyQuote'])->name('invoices.copy-quote');
            Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
            Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
            Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post')->middleware(['permission:invoices.post', 'sod:invoice']);
            Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void')->middleware(['permission:invoices.void', 'sod:invoice']);
            Route::get('invoices/{invoice}/print', [InvoiceController::class, 'printPdf'])->name('invoices.print');

            // Credit Notes
            Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes.index');
            Route::get('credit-notes/create', [CreditNoteController::class, 'create'])->name('credit-notes.create');
            Route::post('credit-notes', [CreditNoteController::class, 'store'])->name('credit-notes.store');
            Route::get('credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('credit-notes.show');
            Route::post('credit-notes/{creditNote}/post', [CreditNoteController::class, 'post'])->name('credit-notes.post')->middleware(['permission:credit-notes.post', 'sod:creditNote']);
            Route::get('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'applyForm'])->name('credit-notes.apply-form');
            Route::post('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply'])->name('credit-notes.apply');
            Route::post('credit-notes/{creditNote}/void', [CreditNoteController::class, 'void'])->name('credit-notes.void')->middleware(['permission:credit-notes.void', 'sod:creditNote']);

            // ── Quotations ──
            Route::get('quotations', [QuotationController::class, 'index'])->name('quotations.index');
            Route::get('quotations/export', [QuotationController::class, 'export'])->name('quotations.export');
            Route::get('quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
            Route::post('quotations', [QuotationController::class, 'store'])->name('quotations.store');
            Route::get('quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
            Route::get('quotations/{quotation}/edit', [QuotationController::class, 'edit'])->name('quotations.edit');
            Route::put('quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update');
            Route::delete('quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy')->middleware(['permission:quotations.edit', 'sod:quotation']);
            Route::post('quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
            Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('quotations.accept')->middleware(['permission:quotations.approve', 'sod:quotation']);
            Route::post('quotations/{quotation}/decline', [QuotationController::class, 'decline'])->name('quotations.decline')->middleware(['permission:quotations.approve', 'sod:quotation']);
            Route::post('quotations/{quotation}/convert-to-invoice', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert-to-invoice')->middleware(['permission:quotations.convert', 'sod:quotation']);
            Route::post('quotations/{quotation}/convert-to-receipt', [QuotationController::class, 'convertToSalesReceipt'])->name('quotations.convert-to-receipt')->middleware(['permission:quotations.convert', 'sod:quotation']);
            Route::post('quotations/{quotation}/void', [QuotationController::class, 'void'])->name('quotations.void')->middleware(['permission:quotations.void', 'sod:quotation']);
            Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
            Route::post('quotations/{quotation}/email', [QuotationController::class, 'email'])->name('quotations.email');

            // ── Sales Orders ──
            Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
            Route::get('sales-orders/export', [SalesOrderController::class, 'export'])->name('sales-orders.export');
            Route::get('sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
            Route::post('sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
            Route::get('sales-orders/{order}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
            Route::get('sales-orders/{order}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
            Route::put('sales-orders/{order}', [SalesOrderController::class, 'update'])->name('sales-orders.update');
            Route::delete('sales-orders/{order}', [SalesOrderController::class, 'destroy'])->name('sales-orders.destroy')->middleware(['permission:sales-orders.edit', 'sod:order']);
            Route::post('sales-orders/{order}/send', [SalesOrderController::class, 'send'])->name('sales-orders.send');
            Route::post('sales-orders/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm')->middleware(['permission:sales-orders.confirm', 'sod:order']);
            Route::post('sales-orders/{order}/fulfill', [SalesOrderController::class, 'markFulfilled'])->name('sales-orders.fulfill')->middleware(['permission:sales-orders.convert', 'sod:order']);
            Route::post('sales-orders/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel')->middleware(['permission:sales-orders.cancel', 'sod:order']);
            Route::post('sales-orders/{order}/convert-to-invoice', [SalesOrderController::class, 'convertToInvoice'])->name('sales-orders.convert-to-invoice')->middleware(['permission:sales-orders.convert', 'sod:order']);
            Route::post('sales-orders/{order}/convert-to-receipt', [SalesOrderController::class, 'convertToSalesReceipt'])->name('sales-orders.convert-to-receipt')->middleware(['permission:sales-orders.convert', 'sod:order']);
            Route::post('sales-orders/{order}/void', [SalesOrderController::class, 'void'])->name('sales-orders.void')->middleware(['permission:sales-orders.void', 'sod:order']);
            Route::get('sales-orders/{order}/print', [SalesOrderController::class, 'print'])->name('sales-orders.print');

            // ── Sales Receipts ──
            Route::get('sales-receipts', [SalesReceiptController::class, 'index'])->name('sales-receipts.index');
            Route::get('sales-receipts/export', [SalesReceiptController::class, 'export'])->name('sales-receipts.export');
            Route::get('sales-receipts/create', [SalesReceiptController::class, 'create'])->name('sales-receipts.create');
            Route::post('sales-receipts', [SalesReceiptController::class, 'store'])->name('sales-receipts.store');
            Route::get('sales-receipts/{salesReceipt}', [SalesReceiptController::class, 'show'])->name('sales-receipts.show');
            Route::get('sales-receipts/{salesReceipt}/edit', [SalesReceiptController::class, 'edit'])->name('sales-receipts.edit');
            Route::put('sales-receipts/{salesReceipt}', [SalesReceiptController::class, 'update'])->name('sales-receipts.update');
            Route::delete('sales-receipts/{salesReceipt}', [SalesReceiptController::class, 'destroy'])->name('sales-receipts.destroy')->middleware(['permission:sales-receipts.edit', 'sod:salesReceipt']);
            Route::get('sales-receipts/{salesReceipt}/post-page', [SalesReceiptController::class, 'postPage'])->name('sales-receipts.post-page')->middleware(['permission:sales-receipts.post']);
            Route::post('sales-receipts/{salesReceipt}/post', [SalesReceiptController::class, 'post'])->name('sales-receipts.post')->middleware(['permission:sales-receipts.post', 'sod:salesReceipt']);
            Route::post('sales-receipts/{salesReceipt}/void', [SalesReceiptController::class, 'void'])->name('sales-receipts.void')->middleware(['permission:sales-receipts.void', 'sod:salesReceipt']);
            Route::get('sales-receipts/{salesReceipt}/print', [SalesReceiptController::class, 'print'])->name('sales-receipts.print');
            Route::post('sales-receipts/{salesReceipt}/email', [SalesReceiptController::class, 'email'])->name('sales-receipts.email');

            // ── Sales Register ──
            Route::get('sales-register', [SalesRegisterController::class, 'index'])->name('sales-register.index');

            // Customer Payments
            Route::get('customer-payments/create', [CustomerPaymentController::class, 'create'])->name('customer-payments.create');
            Route::post('customer-payments', [CustomerPaymentController::class, 'store'])->name('customer-payments.store');
            Route::get('customer-payments/{payment}', [CustomerPaymentController::class, 'show'])->name('customer-payments.show');

            // Bills
            Route::get('bills', [BillController::class, 'index'])->name('bills.index');
            Route::get('bills/create', [BillController::class, 'create'])->name('bills.create');
            Route::post('bills', [BillController::class, 'store'])->name('bills.store');
            Route::get('bills/{bill}', [BillController::class, 'show'])->name('bills.show');
            Route::get('bills/{bill}/edit', [BillController::class, 'edit'])->name('bills.edit');
            Route::put('bills/{bill}', [BillController::class, 'update'])->name('bills.update');
            Route::post('bills/{bill}/submit', [BillController::class, 'submit'])->name('bills.submit')->middleware(['permission:bills.edit', 'sod:bill']);
            Route::post('bills/{bill}/post', [BillController::class, 'post'])->name('bills.post')->middleware(['permission:bills.post', 'sod:bill']);
            Route::post('bills/{bill}/approve', [BillController::class, 'approve'])->name('bills.approve')->middleware(['permission:bills.approve', 'sod:bill']);
            Route::post('bills/{bill}/void', [BillController::class, 'void'])->name('bills.void')->middleware(['permission:bills.void', 'sod:bill']);

            // Vendor Credits
            Route::get('vendor-credits', [VendorCreditController::class, 'index'])->name('vendor-credits.index');
            Route::get('vendor-credits/create', [VendorCreditController::class, 'create'])->name('vendor-credits.create');
            Route::post('vendor-credits', [VendorCreditController::class, 'store'])->name('vendor-credits.store');
            Route::get('vendor-credits/{vendorCredit}', [VendorCreditController::class, 'show'])->name('vendor-credits.show');
            Route::post('vendor-credits/{vendorCredit}/post', [VendorCreditController::class, 'post'])->name('vendor-credits.post')->middleware(['permission:vendor-credits.post', 'sod:vendorCredit']);
            Route::get('vendor-credits/{vendorCredit}/apply', [VendorCreditController::class, 'applyForm'])->name('vendor-credits.apply-form');
            Route::post('vendor-credits/{vendorCredit}/apply', [VendorCreditController::class, 'apply'])->name('vendor-credits.apply');
            Route::post('vendor-credits/{vendorCredit}/void', [VendorCreditController::class, 'void'])->name('vendor-credits.void')->middleware(['permission:vendor-credits.void', 'sod:vendorCredit']);

            // Vendor Payments
            Route::get('vendor-payments', [VendorPaymentController::class, 'index'])->name('vendor-payments.index');
            Route::get('vendor-payments/create', [VendorPaymentController::class, 'create'])->name('vendor-payments.create');
            Route::post('vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store');
            Route::get('vendor-payments/{payment}', [VendorPaymentController::class, 'show'])->name('vendor-payments.show');
            Route::post('vendor-payments/{payment}/submit', [VendorPaymentController::class, 'submit'])->name('vendor-payments.submit')->middleware(['permission:vendor-payments.submit', 'sod:payment']);
            Route::post('vendor-payments/{payment}/approve', [VendorPaymentController::class, 'approve'])->name('vendor-payments.approve')->middleware(['permission:vendor-payments.approve', 'sod:payment']);
            Route::post('vendor-payments/{payment}/reject', [VendorPaymentController::class, 'reject'])->name('vendor-payments.reject')->middleware(['permission:vendor-payments.reject', 'sod:payment']);

            // ── Expenses ──
            Route::get('expenses/dashboard', [ExpenseController::class, 'dashboard'])->name('expenses.dashboard');
            Route::get('expenses/claims', [ExpenseController::class, 'claimsIndex'])->name('expenses.claims.index');
            Route::get('expenses/claims/create', [ExpenseController::class, 'claimCreate'])->name('expenses.claims.create');
            Route::post('expenses/claims', [ExpenseController::class, 'claimStore'])->name('expenses.claims.store');
            Route::post('expenses/claims/{claim}/submit', [ExpenseController::class, 'claimSubmit'])->name('expenses.claims.submit')->middleware(['permission:expense-claims.submit']);
            Route::post('expenses/claims/{claim}/approve', [ExpenseController::class, 'claimApprove'])->name('expenses.claims.approve')->middleware(['permission:expense-claims.approve', 'sod:claim']);
            Route::post('expenses/claims/{claim}/reject', [ExpenseController::class, 'claimReject'])->name('expenses.claims.reject')->middleware(['permission:expense-claims.reject', 'sod:claim']);
            Route::post('expenses/claims/{claim}/reimburse', [ExpenseController::class, 'claimReimburse'])->name('expenses.claims.reimburse')->middleware(['permission:expense-claims.reimburse', 'sod:claim']);
            Route::delete('expenses/claims/{claim}', [ExpenseController::class, 'claimDestroy'])->name('expenses.claims.destroy')->middleware(['permission:expense-claims.delete', 'sod:claim']);
            Route::get('expenses/claims/{claim}', [ExpenseController::class, 'claimShow'])->name('expenses.claims.show');
            Route::get('expenses/recurring', [ExpenseController::class, 'recurringIndex'])->name('expenses.recurring.index');
            Route::get('expenses/recurring/create', [ExpenseController::class, 'recurringCreate'])->name('expenses.recurring.create');
            Route::post('expenses/recurring', [ExpenseController::class, 'recurringStore'])->name('expenses.recurring.store');
            Route::get('expenses/recurring/{template}/edit', [ExpenseController::class, 'recurringEdit'])->name('expenses.recurring.edit');
            Route::put('expenses/recurring/{template}', [ExpenseController::class, 'recurringUpdate'])->name('expenses.recurring.update');
            Route::post('expenses/recurring/{template}/toggle', [ExpenseController::class, 'recurringToggle'])->name('expenses.recurring.toggle')->middleware(['permission:expense-recurring.edit', 'sod:template']);
            Route::delete('expenses/recurring/{template}', [ExpenseController::class, 'recurringDestroy'])->name('expenses.recurring.destroy')->middleware(['permission:expense-recurring.delete', 'sod:template']);
            Route::get('expenses/categories', [ExpenseController::class, 'categoriesIndex'])->name('expenses.categories.index');
            Route::post('expenses/categories', [ExpenseController::class, 'categoryStore'])->name('expenses.categories.store');
            Route::put('expenses/categories/{category}', [ExpenseController::class, 'categoryUpdate'])->name('expenses.categories.update');
            Route::delete('expenses/categories/{category}', [ExpenseController::class, 'categoryDestroy'])->name('expenses.categories.destroy');
            Route::get('expenses/reports', [ExpenseController::class, 'reports'])->name('expenses.reports');
            Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
            Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
            Route::post('expenses/{expense}/submit', [ExpenseController::class, 'submit'])->name('expenses.submit')->middleware(['permission:expenses.submit']);
            Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve')->middleware(['permission:expenses.approve', 'sod:expense']);
            Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject')->middleware(['permission:expenses.reject', 'sod:expense']);
            Route::post('expenses/{expense}/return', [ExpenseController::class, 'returnForCorrection'])->name('expenses.return')->middleware(['permission:expenses.return', 'sod:expense']);
            Route::post('expenses/{expense}/budget-authorize', [ExpenseController::class, 'authorizeBudget'])->name('expenses.budget-authorize')->middleware(['permission:expenses.approve', 'sod:expense']);
            Route::post('expenses/{expense}/post', [ExpenseController::class, 'post'])->name('expenses.post')->middleware(['permission:expenses.post', 'sod:expense']);
            Route::post('expenses/{expense}/pay', [ExpenseController::class, 'recordPayment'])->name('expenses.pay')->middleware(['permission:expenses.pay', 'sod:expense']);
            Route::post('expenses/{expense}/void', [ExpenseController::class, 'void'])->name('expenses.void')->middleware(['permission:expenses.void', 'sod:expense']);
            Route::post('expenses/{expense}/duplicate', [ExpenseController::class, 'duplicate'])->name('expenses.duplicate')->middleware(['permission:expenses.duplicate']);
            Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware(['permission:expenses.delete', 'sod:expense']);
            Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');

            Route::middleware('feature:purchasing')->group(function () {
            // Purchase Requisitions
                Route::get('purchase-requisitions', [PurchaseRequisitionController::class, 'index'])->name('purchase-requisitions.index');
                Route::get('purchase-requisitions/create', [PurchaseRequisitionController::class, 'create'])->name('purchase-requisitions.create');
                Route::get('purchase-requisitions/export', [PurchaseRequisitionController::class, 'exportCsv'])->name('purchase-requisitions.export');
                Route::post('purchase-requisitions', [PurchaseRequisitionController::class, 'store'])->name('purchase-requisitions.store');
                Route::post('purchase-requisitions/budget-check', [PurchaseRequisitionController::class, 'budgetCheck'])->name('purchase-requisitions.budget-check');
                Route::get('purchase-requisitions/{purchaseRequisition}', [PurchaseRequisitionController::class, 'show'])->name('purchase-requisitions.show');
                Route::get('purchase-requisitions/{purchaseRequisition}/edit', [PurchaseRequisitionController::class, 'edit'])->name('purchase-requisitions.edit');
                Route::put('purchase-requisitions/{purchaseRequisition}', [PurchaseRequisitionController::class, 'update'])->name('purchase-requisitions.update');
                Route::delete('purchase-requisitions/{purchaseRequisition}', [PurchaseRequisitionController::class, 'destroy'])->name('purchase-requisitions.destroy')->middleware(['permission:purchase-requisitions.edit']);
                Route::post('purchase-requisitions/{purchaseRequisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('purchase-requisitions.submit')->middleware(['permission:purchase-requisitions.submit']);
                Route::post('purchase-requisitions/{purchaseRequisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('purchase-requisitions.approve')->middleware(['permission:purchase-requisitions.approve', 'sod:purchaseRequisition']);
                Route::post('purchase-requisitions/{purchaseRequisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('purchase-requisitions.reject')->middleware(['permission:purchase-requisitions.reject', 'sod:purchaseRequisition']);

            // Purchase Orders
                Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
                Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
                Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
                Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
                Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
                Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
                Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])->name('purchase-orders.confirm')->middleware(['permission:purchase-orders.confirm', 'sod:purchaseOrder']);
                Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel')->middleware(['permission:purchase-orders.cancel', 'sod:purchaseOrder']);
                Route::post('purchase-orders/{purchaseOrder}/convert-to-bill', [PurchaseOrderController::class, 'convert'])->name('purchase-orders.convert')->middleware(['permission:bills.create', 'sod:purchaseOrder']);

            // Goods Received Notes
                Route::get('goods-received-notes', [GoodsReceivedNoteController::class, 'index'])->name('goods-received-notes.index');
                Route::get('goods-received-notes/create', [GoodsReceivedNoteController::class, 'create'])->name('goods-received-notes.create');
                Route::post('goods-received-notes', [GoodsReceivedNoteController::class, 'store'])->name('goods-received-notes.store');
                Route::get('goods-received-notes/{goodsReceivedNote}', [GoodsReceivedNoteController::class, 'show'])->name('goods-received-notes.show');
                Route::post('goods-received-notes/{goodsReceivedNote}/post', [GoodsReceivedNoteController::class, 'post'])->name('goods-received-notes.post')->middleware('sod:goodsReceivedNote');
            });

            Route::middleware('feature:banking')->group(function () {
            // Banking Centre
                Route::get('banking', [BankingCentreController::class, 'index'])->name('banking.dashboard');
                Route::get('banking/accounts', [BankingAccountController::class, 'index'])->name('banking.accounts');
                Route::get('banking/accounts/create', [BankingAccountController::class, 'create'])->name('banking.accounts.create');
                Route::post('banking/accounts', [BankingAccountController::class, 'store'])->name('banking.accounts.store');
                Route::get('banking/accounts/{bankAccountId}/edit', [BankingAccountController::class, 'edit'])->name('banking.accounts.edit');
                Route::put('banking/accounts/{bankAccountId}', [BankingAccountController::class, 'update'])->name('banking.accounts.update');
                Route::post('banking/accounts/{bankAccountId}/toggle', [BankingAccountController::class, 'toggle'])->name('banking.accounts.toggle');
                Route::get('banking/accounts/{bankAccountId}/register', [BankingRegisterController::class, 'index'])->name('banking.register');
                Route::get('banking/accounts/{bankAccountId}/register/new', [BankingRegisterController::class, 'newTransaction'])->name('banking.new-transaction');
                Route::post('banking/accounts/{bankAccountId}/register', [BankingRegisterController::class, 'storeTransaction'])->name('banking.store-transaction');
                Route::get('banking/transfers', [BankingTransferController::class, 'index'])->name('banking.transfers');
                Route::get('banking/transfers/create', [BankingTransferController::class, 'create'])->name('banking.transfers.create');
                Route::post('banking/transfers', [BankingTransferController::class, 'store'])->name('banking.transfers.store');
                Route::get('banking/deposits', [BankingDepositController::class, 'index'])->name('banking.deposits');
                Route::get('banking/deposits/create', [BankingDepositController::class, 'create'])->name('banking.deposits.create');
                Route::post('banking/deposits', [BankingDepositController::class, 'store'])->name('banking.deposits.store');
                Route::get('banking/cheques', [BankingChequeController::class, 'index'])->name('banking.cheques');
                Route::get('banking/cheques/create', [BankingChequeController::class, 'create'])->name('banking.cheques.create');
                Route::post('banking/cheques', [BankingChequeController::class, 'store'])->name('banking.cheques.store');
                Route::get('banking/cheques/{cheque}', [BankingChequeController::class, 'show'])->name('banking.cheques.show');
                Route::post('banking/cheques/{cheque}/void', [BankingChequeController::class, 'void'])->name('banking.cheques.void')->middleware('sod:cheque');
                Route::post('banking/cheques/{cheque}/clear', [BankingChequeController::class, 'clear'])->name('banking.cheques.clear')->middleware('sod:cheque');
                Route::get('banking/petty', [BankingPettyCashController::class, 'index'])->name('banking.petty');
                Route::get('banking/petty/create', [BankingPettyCashController::class, 'create'])->name('banking.petty.create');
                Route::post('banking/petty', [BankingPettyCashController::class, 'store'])->name('banking.petty.store');
                Route::get('banking/petty/{fund}', [BankingPettyCashController::class, 'show'])->name('banking.petty.show');
                Route::post('banking/petty/{fund}/establish', [BankingPettyCashController::class, 'establish'])->name('banking.petty.establish')->middleware('sod:fund');
                Route::post('banking/petty/{fund}/expense', [BankingPettyCashController::class, 'expense'])->name('banking.petty.expense')->middleware('sod:fund');
                Route::post('banking/petty/{fund}/replenish', [BankingPettyCashController::class, 'replenish'])->name('banking.petty.replenish')->middleware('sod:fund');
                Route::get('banking/reports', [BankingReportsController::class, 'index'])->name('banking.reports');

            // Bank Reconciliation
                Route::get('bank-reconciliations/create', [BankReconciliationController::class, 'create'])->name('bank-reconciliation.create');
                Route::post('bank-reconciliations', [BankReconciliationController::class, 'store'])->name('bank-reconciliation.store');
                Route::get('bank-reconciliations/export', [BankReconciliationController::class, 'export'])->name('bank-reconciliation.export');
                Route::get('bank-reconciliations/print', [BankReconciliationController::class, 'print'])->name('bank-reconciliation.print');
                Route::get('bank-reconciliations/statements', [BankReconciliationController::class, 'statements'])->name('bank-reconciliation.statements');
                Route::get('bank-reconciliations/adjustments', [BankReconciliationController::class, 'adjustmentsList'])->name('bank-reconciliation.adjustments');
                Route::get('bank-reconciliations/outstanding', [BankReconciliationController::class, 'outstanding'])->name('bank-reconciliation.outstanding');
                Route::get('bank-reconciliations/reports', [BankReconciliationController::class, 'reports'])->name('bank-reconciliation.reports');
                Route::get('bank-reconciliations/audit', [BankReconciliationController::class, 'auditAll'])->name('bank-reconciliation.audit-all');
                Route::post('bank-reconciliations/approval', [BankReconciliationController::class, 'toggleApproval'])->name('bank-reconciliation.approval');
                Route::get('bank-reconciliations/reports/{report}/{reconciliation?}', [BankReconciliationController::class, 'report'])->name('bank-reconciliation.report');
                Route::get('bank-reconciliations/{bankAccountId?}', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
                Route::get('bank-reconciliations/{reconciliation}/workspace', [BankReconciliationController::class, 'workspace'])->name('bank-reconciliation.workspace');
                Route::get('bank-reconciliations/{reconciliation}/detail', [BankReconciliationController::class, 'show'])->name('bank-reconciliation.show');
                Route::get('bank-reconciliations/{reconciliation}/audit', [BankReconciliationController::class, 'audit'])->name('bank-reconciliation.audit');
                Route::get('bank-reconciliations/{reconciliation}/import', [BankReconciliationController::class, 'importForm'])->name('bank-reconciliation.import');
                Route::post('bank-reconciliations/{reconciliation}/import/preview', [BankReconciliationController::class, 'previewImport'])->name('bank-reconciliation.import.preview');
                Route::post('bank-reconciliations/{reconciliation}/import', [BankReconciliationController::class, 'importStatement'])->name('bank-reconciliation.import.submit');
                Route::post('bank-reconciliations/{reconciliation}/auto-match', [BankReconciliationController::class, 'autoMatch'])->name('bank-reconciliation.auto-match');
                Route::post('bank-reconciliations/{reconciliation}/match', [BankReconciliationController::class, 'match'])->name('bank-reconciliation.match');
                Route::post('bank-reconciliations/{reconciliation}/unmatch', [BankReconciliationController::class, 'unmatch'])->name('bank-reconciliation.unmatch');
                Route::post('bank-reconciliations/{reconciliation}/adjustments', [BankReconciliationController::class, 'addAdjustment'])->name('bank-reconciliation.adjustments.store');
                Route::delete('bank-reconciliations/{reconciliation}/adjustments/{adjustmentId}', [BankReconciliationController::class, 'removeAdjustment'])->name('bank-reconciliation.adjustments.destroy');
                Route::post('bank-reconciliations/{reconciliation}/ready', [BankReconciliationController::class, 'markReadyForReview'])->name('bank-reconciliation.ready');
                Route::post('bank-reconciliations/{reconciliation}/reopen', [BankReconciliationController::class, 'reopen'])->name('bank-reconciliation.reopen');
                Route::post('bank-reconciliations/{reconciliation}/approve', [BankReconciliationController::class, 'approve'])->name('bank-reconciliation.approve');
                Route::post('bank-reconciliations/{reconciliation}/complete', [BankReconciliationController::class, 'complete'])->name('bank-reconciliation.complete');
                Route::post('bank-reconciliations/{reconciliation}/reverse', [BankReconciliationController::class, 'reverse'])->name('bank-reconciliation.reverse');

            // Cash Position
                Route::get('cash-position', [CashPositionController::class, 'index'])->name('cash-position.index');
                Route::get('cash-position/export/csv', [CashPositionController::class, 'exportCsv'])->name('cash-position.export-csv');
                Route::get('cash-position/export/pdf', [CashPositionController::class, 'exportPdf'])->name('cash-position.export-pdf');
                Route::get('cash-position/print', [CashPositionController::class, 'print'])->name('cash-position.print');

            // Legacy aliases (CP + BR still reference these route names)
                Route::get('bank-accounts', [BankingAccountController::class, 'index'])->name('bank-accounts.index');
                Route::get('bank-accounts/{bankAccountId}/register', [BankingRegisterController::class, 'index'])->name('bank-accounts.register');
                Route::get('bank-accounts/transfer', [BankingTransferController::class, 'create'])->name('bank-accounts.transfer-form');
                Route::post('bank-accounts/transfer', [BankingTransferController::class, 'store'])->name('bank-accounts.transfer');
                Route::get('bank-accounts/{bankAccountId}/manual', [BankingRegisterController::class, 'newTransaction'])->name('bank-accounts.manual-form');
                Route::post('bank-accounts/{bankAccountId}/manual', [BankingRegisterController::class, 'storeTransaction'])->name('bank-accounts.store-manual');
                Route::get('petty-cash', [BankingPettyCashController::class, 'index'])->name('petty-cash.index');
            });

            // Financial Statements
            Route::get('income-statement', [IncomeStatementController::class, 'index'])->name('income-statement.index');
            Route::get('income-statement/export/csv', [IncomeStatementController::class, 'exportCsv'])->name('income-statement.export-csv');
            Route::get('income-statement/export/pdf', [IncomeStatementController::class, 'exportPdf'])->name('income-statement.export-pdf');

            Route::get('balance-sheet', [BalanceSheetController::class, 'index'])->name('balance-sheet.index');
            Route::get('balance-sheet/export/csv', [BalanceSheetController::class, 'exportCsv'])->name('balance-sheet.export-csv');
            Route::get('balance-sheet/export/pdf', [BalanceSheetController::class, 'exportPdf'])->name('balance-sheet.export-pdf');

            Route::get('equity-statement', [EquityStatementController::class, 'index'])->name('equity-statement.index');
            Route::get('equity-statement/export/csv', [EquityStatementController::class, 'exportCsv'])->name('equity-statement.export-csv');
            Route::get('equity-statement/export/pdf', [EquityStatementController::class, 'exportPdf'])->name('equity-statement.export-pdf');

            Route::get('cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');
            Route::get('cash-flow/export/csv', [CashFlowController::class, 'exportCsv'])->name('cash-flow.export-csv');
            Route::get('cash-flow/export/pdf', [CashFlowController::class, 'exportPdf'])->name('cash-flow.export-pdf');

            // Aging Reports
            Route::get('aging/ar-summary', [AgingReportController::class, 'arSummary'])->name('aging.ar-summary');
            Route::get('aging/ar-detail', [AgingReportController::class, 'arDetail'])->name('aging.ar-detail');
            Route::get('aging/ap-summary', [AgingReportController::class, 'apSummary'])->name('aging.ap-summary');
            Route::get('aging/ap-detail', [AgingReportController::class, 'apDetail'])->name('aging.ap-detail');
            Route::get('aging/export/csv', [AgingReportController::class, 'exportCsv'])->name('aging.export-csv');

            // Budgeting module
            Route::middleware('feature:budgets')->prefix('budgeting')->name('budgets.')->group(function () {
                // Literal routes FIRST — before /{budget} to avoid param-route shadowing
                Route::get('/', [BudgetController::class, 'dashboard'])->name('dashboard');
                Route::get('/list', [BudgetController::class, 'index'])->name('index');
                Route::get('/create', [BudgetController::class, 'create'])->name('create');
                Route::post('/', [BudgetController::class, 'store'])->name('store');
                Route::get('/vs-actual/report', [BudgetController::class, 'vsActual'])->name('vsactual');
                Route::get('/forecast/report', [BudgetController::class, 'forecast'])->name('forecast');
                Route::get('/adjustments/list', [BudgetController::class, 'adjustments'])->name('adjustments');
                Route::post('/adjustments', [BudgetController::class, 'storeAdjustment'])->name('adjustments.store');
                Route::get('/alerts/list', [BudgetController::class, 'alerts'])->name('alerts');
                Route::post('/alert-rules', [BudgetController::class, 'storeAlertRule'])->name('alert-rules.store');
                Route::get('/settings', [BudgetController::class, 'settings'])->name('settings');
                Route::get('/templates', [BudgetController::class, 'templates'])->name('templates');
                Route::post('/templates', [BudgetController::class, 'storeTemplate'])->name('templates.store');
                Route::get('/reports/index', [BudgetController::class, 'reports'])->name('reports');

                // Parameter routes LAST
                Route::get('/{budget}', [BudgetController::class, 'show'])->name('show');
                Route::get('/{budget}/edit', [BudgetController::class, 'edit'])->name('edit');
                Route::put('/{budget}', [BudgetController::class, 'update'])->name('update');
                Route::post('/{budget}/submit', [BudgetController::class, 'submit'])->name('submit');
                Route::post('/{budget}/approve', [BudgetController::class, 'approve'])->name('approve');
                Route::post('/{budget}/reject', [BudgetController::class, 'reject'])->name('reject');
                Route::post('/{budget}/lock', [BudgetController::class, 'lock'])->name('lock');
                Route::post('/{budget}/unlock', [BudgetController::class, 'unlock'])->name('unlock');
                Route::post('/adjustments/{adjustment}/approve', [BudgetController::class, 'approveAdjustment'])->name('adjustments.approve');
                Route::post('/adjustments/{adjustment}/reject', [BudgetController::class, 'rejectAdjustment'])->name('adjustments.reject');
                Route::post('/alerts/{alert}/read', [BudgetController::class, 'markAlertRead'])->name('alerts.read');
            });

            Route::middleware('feature:fixed_assets')->group(function () {
            // Fixed Assets
                Route::resource('asset-categories', \App\Http\Controllers\Accounting\AssetCategoryController::class);
                Route::resource('fixed-assets', \App\Http\Controllers\Accounting\FixedAssetController::class);
                Route::post('fixed-assets/{asset}/activate', [\App\Http\Controllers\Accounting\FixedAssetController::class, 'activate'])->name('fixed-assets.activate');
                Route::get('fixed-assets/{asset}/schedule', [\App\Http\Controllers\Accounting\AssetDepreciationController::class, 'schedule'])->name('fixed-assets.schedule');
                Route::get('asset-depreciation/runs', [\App\Http\Controllers\Accounting\AssetDepreciationController::class, 'runHistory'])->name('depreciation.runs');
                Route::post('asset-depreciation/run', [\App\Http\Controllers\Accounting\AssetDepreciationController::class, 'run'])->name('depreciation.run');
                Route::resource('asset-disposals', \App\Http\Controllers\Accounting\AssetDisposalController::class);
                Route::resource('asset-transfers', \App\Http\Controllers\Accounting\AssetTransferController::class);
                Route::resource('asset-impairments', \App\Http\Controllers\Accounting\AssetImpairmentController::class);
                Route::post('asset-impairments/reverse', [\App\Http\Controllers\Accounting\AssetImpairmentController::class, 'reverse'])->name('asset-impairments.reverse');
                Route::resource('asset-revaluations', \App\Http\Controllers\Accounting\AssetRevaluationController::class);
                Route::get('asset-usage', [\App\Http\Controllers\Accounting\AssetDepreciationController::class, 'usageLog'])->name('asset-usage.index');
                Route::post('asset-usage', [\App\Http\Controllers\Accounting\AssetDepreciationController::class, 'storeUsage'])->name('asset-usage.store');
            });

            // Account Classification
            Route::get('account-classification', [AccountClassificationController::class, 'index'])->name('account-classification.index');
            Route::post('account-classification', [AccountClassificationController::class, 'update'])->name('account-classification.update');

            // UOM Conversions
            Route::get('uom-conversions', [\App\Http\Controllers\Accounting\UomConversionController::class, 'index'])->name('uom-conversions.index');
            Route::get('uom-conversions/{product}/edit', [\App\Http\Controllers\Accounting\UomConversionController::class, 'edit'])->name('uom-conversions.edit');
            Route::put('uom-conversions/{product}', [\App\Http\Controllers\Accounting\UomConversionController::class, 'update'])->name('uom-conversions.update');

            // Landed Cost
            Route::get('landed-costs', [\App\Http\Controllers\Accounting\LandedCostController::class, 'index'])->name('landed-costs.index');
            Route::get('landed-costs/create', [\App\Http\Controllers\Accounting\LandedCostController::class, 'create'])->name('landed-costs.create');
            Route::post('landed-costs', [\App\Http\Controllers\Accounting\LandedCostController::class, 'store'])->name('landed-costs.store');
            Route::get('landed-costs/{voucher}', [\App\Http\Controllers\Accounting\LandedCostController::class, 'show'])->name('landed-costs.show');
            Route::post('landed-costs/{voucher}/post', [\App\Http\Controllers\Accounting\LandedCostController::class, 'post'])->name('landed-costs.post')->middleware('sod:voucher');

            // ── Report Center ──
            Route::get('report-center', [ReportCenterController::class, 'index'])->name('report-center.index');
            Route::post('report-center/favorite/{key}', [ReportCenterController::class, 'toggleFavorite'])->name('report-center.toggle-favorite');

            // ── New Reports ──
            // Financial Statements
            Route::get('reports/journal', [\App\Http\Controllers\Accounting\ReportControllers\JournalReportController::class, 'index'])->name('reports.journal');
            Route::get('reports/trial-balance-comparison', [\App\Http\Controllers\Accounting\ReportControllers\TrialBalanceComparisonController::class, 'index'])->name('reports.trial-balance-comparison');

            // Sales
            Route::get('reports/sales-by-customer', [\App\Http\Controllers\Accounting\ReportControllers\SalesByCustomerController::class, 'index'])->name('reports.sales-by-customer');
            Route::get('reports/sales-by-item', [\App\Http\Controllers\Accounting\ReportControllers\SalesByItemController::class, 'index'])->name('reports.sales-by-item');
            Route::get('reports/customer-credit-balance', [\App\Http\Controllers\Accounting\ReportControllers\CustomerCreditBalanceController::class, 'index'])->name('reports.customer-credit-balance');
            Route::get('reports/quotation-status', [\App\Http\Controllers\Accounting\ReportControllers\QuotationStatusController::class, 'index'])->name('reports.quotation-status');
            Route::get('reports/quotation-register', [\App\Http\Controllers\Accounting\ReportControllers\QuotationRegisterController::class, 'index'])->name('reports.quotation-register');
            Route::get('reports/sales-pipeline', [\App\Http\Controllers\Accounting\ReportControllers\SalesPipelineController::class, 'index'])->name('reports.sales-pipeline');
            Route::get('reports/sales-receipts/daily-summary', [\App\Http\Controllers\Accounting\ReportControllers\DailySummaryController::class, 'index'])->name('reports.sales-receipts.daily-summary');
            Route::get('reports/sales-receipts/cashbook', [\App\Http\Controllers\Accounting\ReportControllers\SalesReceiptsCashbookController::class, 'index'])->name('reports.sales-receipts.cashbook');

            // Purchasing
            Route::middleware('feature:purchasing')->group(function () {
                Route::get('reports/purchase-register', [\App\Http\Controllers\Accounting\ReportControllers\PurchaseRegisterController::class, 'index'])->name('reports.purchase-register');
                Route::get('reports/purchases-by-vendor', [\App\Http\Controllers\Accounting\ReportControllers\PurchasesByVendorController::class, 'index'])->name('reports.purchases-by-vendor');
                Route::get('reports/purchases-by-item', [\App\Http\Controllers\Accounting\ReportControllers\PurchasesByItemController::class, 'index'])->name('reports.purchases-by-item');
                Route::get('reports/unbilled-receipts', [\App\Http\Controllers\Accounting\ReportControllers\UnbilledReceiptsController::class, 'index'])->name('reports.unbilled-receipts');
                Route::get('reports/po-status', [\App\Http\Controllers\Accounting\ReportControllers\PurchaseOrderStatusController::class, 'index'])->name('reports.po-status');
                Route::get('reports/vendor-credit-balance', [\App\Http\Controllers\Accounting\ReportControllers\VendorCreditBalanceController::class, 'index'])->name('reports.vendor-credit-balance');
            });

            // Inventory
            Route::middleware('feature:inventory')->group(function () {
                Route::get('reports/stock-movement', [\App\Http\Controllers\Accounting\ReportControllers\StockMovementController::class, 'index'])->name('reports.stock-movement');
                Route::get('reports/stock-count-variance', [\App\Http\Controllers\Accounting\ReportControllers\StockCountVarianceController::class, 'index'])->name('reports.stock-count-variance');
                Route::get('reports/item-profitability', [\App\Http\Controllers\Accounting\ReportControllers\ItemProfitabilityController::class, 'index'])->name('reports.item-profitability');
            });

            // Banking
            Route::middleware('feature:banking')->group(function () {
                Route::get('reports/bank-balances', [\App\Http\Controllers\Accounting\ReportControllers\BankBalancesController::class, 'index'])->name('reports.bank-balances');
                Route::get('reports/deposits-in-transit', [\App\Http\Controllers\Accounting\ReportControllers\DepositsInTransitController::class, 'index'])->name('reports.deposits-in-transit');
            });

            Route::middleware('feature:fixed_assets')->group(function () {
            // Fixed Assets
                Route::get('reports/asset-revaluation', [\App\Http\Controllers\Accounting\ReportControllers\AssetRevaluationReportController::class, 'index'])->name('reports.asset-revaluation');
                Route::get('reports/asset-impairment', [\App\Http\Controllers\Accounting\ReportControllers\AssetImpairmentReportController::class, 'index'])->name('reports.asset-impairment');
            });

            // Payroll
            Route::middleware('feature:payroll')->group(function () {
                Route::get('reports/payroll-register', [\App\Http\Controllers\Accounting\ReportControllers\PayrollRegisterController::class, 'index'])->name('reports.payroll-register');
                Route::get('reports/payroll-summary', [\App\Http\Controllers\Accounting\ReportControllers\PayrollSummaryController::class, 'index'])->name('reports.payroll-summary');
                Route::get('reports/employee-cost-by-branch', [\App\Http\Controllers\Accounting\ReportControllers\EmployeeCostByBranchController::class, 'index'])->name('reports.employee-cost-by-branch');
            });

            // Compliance
            Route::get('reports/period-lock-status', [\App\Http\Controllers\Accounting\ReportControllers\PeriodLockStatusController::class, 'index'])->name('reports.period-lock-status');

            // Batch 4 – Operational & Cross-Module
            Route::get('reports/chart-of-accounts', [\App\Http\Controllers\Accounting\ReportControllers\ChartOfAccountsController::class, 'index'])->name('reports.chart-of-accounts');
            Route::get('reports/customer-statement', [\App\Http\Controllers\Accounting\ReportControllers\CustomerStatementController::class, 'index'])->name('reports.customer-statement');
            Route::get('reports/vendor-statement', [\App\Http\Controllers\Accounting\ReportControllers\VendorStatementController::class, 'index'])->name('reports.vendor-statement');
            Route::get('reports/unbilled-deliveries', [\App\Http\Controllers\Accounting\ReportControllers\UnbilledDeliveriesController::class, 'index'])->name('reports.unbilled-deliveries');
            Route::get('reports/cheque-register', [\App\Http\Controllers\Accounting\ReportControllers\ChequeRegisterController::class, 'index'])->name('reports.cheque-register');

            Route::middleware('feature:fixed_assets')->group(function () {
            // Fixed Assets
                Route::get('reports/asset-disposal-report', [\App\Http\Controllers\Accounting\ReportControllers\AssetDisposalReportController::class, 'index'])->name('reports.asset-disposal-report');
                Route::get('reports/tax-depreciation-schedule', [\App\Http\Controllers\Accounting\ReportControllers\TaxDepreciationScheduleController::class, 'index'])->name('reports.tax-depreciation-schedule');
            });

            // Payroll
            Route::middleware('feature:payroll')->group(function () {
                Route::get('reports/paye-remittance-report', [\App\Http\Controllers\Accounting\ReportControllers\PayeRemittanceReportController::class, 'index'])->name('reports.paye-remittance-report');
                Route::get('reports/pension-remittance-report', [\App\Http\Controllers\Accounting\ReportControllers\PensionRemittanceReportController::class, 'index'])->name('reports.pension-remittance-report');
                Route::get('reports/payslip-report', [\App\Http\Controllers\Accounting\ReportControllers\PayslipReportController::class, 'index'])->name('reports.payslip-report');
            });

            // Consolidated
            Route::get('reports/consolidated-balance-sheet', [\App\Http\Controllers\Accounting\ReportControllers\ConsolidatedBalanceSheetController::class, 'index'])->name('reports.consolidated-balance-sheet');
            Route::get('reports/consolidated-income-statement', [\App\Http\Controllers\Accounting\ReportControllers\ConsolidatedIncomeStatementController::class, 'index'])->name('reports.consolidated-income-statement');

            // Cross-Module
            Route::get('reports/pending-approvals-aging', [\App\Http\Controllers\Accounting\ReportControllers\PendingApprovalsAgingController::class, 'index'])->name('reports.pending-approvals-aging');
            Route::get('reports/eis-submission-status', [\App\Http\Controllers\Accounting\ReportControllers\EisSubmissionStatusController::class, 'index'])->name('reports.eis-submission-status');
            Route::get('reports/assembly-build-history', [\App\Http\Controllers\Accounting\ReportControllers\AssemblyBuildHistoryController::class, 'index'])->name('reports.assembly-build-history');
        });

        // Global search (Mode 2) — expanded role gate so bookkeepers,
        // cashiers and auditors can find records they are permitted to view.
        Route::prefix('accounting/search')->name('accounting.search.')
            ->middleware('role_or_permission:system_admin|company_admin|accountant|approver|viewer|bookkeeper|cashier|auditor')
            ->group(function () {
                Route::get('global', [GlobalSearchController::class, 'global'])->name('global');
                Route::get('any', [GlobalSearchController::class, 'any'])->name('any');
            });

        // Administration
        Route::prefix('admin')->name('admin.')->group(function () {
            // Numbering Sequences
            Route::get('numbering-sequences', [\App\Http\Controllers\Admin\NumberingSequenceController::class, 'index'])->name('numbering-sequences.index');
            Route::get('numbering-sequences/create', [\App\Http\Controllers\Admin\NumberingSequenceController::class, 'create'])->name('numbering-sequences.create');
            Route::post('numbering-sequences', [\App\Http\Controllers\Admin\NumberingSequenceController::class, 'store'])->name('numbering-sequences.store');
            Route::get('numbering-sequences/{numberingSequence}', [\App\Http\Controllers\Admin\NumberingSequenceController::class, 'show'])->name('numbering-sequences.show');
            Route::get('numbering-sequences/{numberingSequence}/edit', [\App\Http\Controllers\Admin\NumberingSequenceController::class, 'edit'])->name('numbering-sequences.edit');
            Route::put('numbering-sequences/{numberingSequence}', [\App\Http\Controllers\Admin\NumberingSequenceController::class, 'update'])->name('numbering-sequences.update');
            Route::post('numbering-sequences/{numberingSequence}/reset', [\App\Http\Controllers\Admin\NumberingSequenceController::class, 'reset'])->name('numbering-sequences.reset');

            // Unified Audit Log
            Route::get('audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log.index');
            Route::get('audit-log/export/csv', [\App\Http\Controllers\Admin\AuditLogController::class, 'exportCsv'])->name('audit-log.export-csv');

            // Security Settings
            Route::get('security', [\App\Http\Controllers\Admin\SecuritySettingsController::class, 'index'])->name('security.index');
            Route::put('security', [\App\Http\Controllers\Admin\SecuritySettingsController::class, 'update'])->name('security.update');

            // Notification Settings
            Route::get('notifications', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'index'])->name('notifications.index');
            Route::put('notifications', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'update'])->name('notifications.update');
            Route::get('notifications/templates/{template}/edit', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'editTemplate'])->name('notifications.template-edit');
            Route::put('notifications/templates/{template}', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'updateTemplate'])->name('notifications.template-update');

            // Backup Management
            Route::get('backups', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('backups.index');
            Route::post('backups/trigger', [\App\Http\Controllers\Admin\BackupController::class, 'trigger'])->name('backups.trigger');
            Route::post('backups/snapshots', [\App\Http\Controllers\Admin\BackupController::class, 'createSnapshot'])->name('backups.create-snapshot');
            Route::patch('backups/snapshots/{backup}/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restoreSnapshot'])->name('backups.restore-snapshot');
            Route::delete('backups/snapshots/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'deleteSnapshot'])->name('backups.delete-snapshot');

            // System Health
            Route::get('system-health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('system-health.index');

            // User Management
            Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
            Route::get('users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
            Route::post('users/{user}/toggle-2fa', [\App\Http\Controllers\Admin\UserController::class, 'toggle2fa'])->name('users.toggle-2fa');

            // Permission Manager
            Route::get('permissions', [\App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('permissions.index');
            Route::post('permissions/sync', [\App\Http\Controllers\Admin\PermissionController::class, 'sync'])->name('permissions.sync');

            // Setup Wizard
            Route::get('setup-wizard', [\App\Http\Controllers\Admin\SetupWizardController::class, 'index'])->name('setup-wizard.index');
            Route::post('setup-wizard', [\App\Http\Controllers\Admin\SetupWizardController::class, 'store'])->name('setup-wizard.store');
        });

        // System Settings
        Route::prefix('system-settings')->name('system-settings.')->group(function () {
            Route::get('/audit-log', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'logs'])->name('audit-log');
            Route::get('/{tab?}', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'index'])->name('index');
            Route::put('/company', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'updateCompany'])->name('update-company');
            Route::put('/regional', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'updateRegional'])->name('update-regional');
            Route::put('/currency', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'updateCurrency'])->name('update-currency');
            Route::put('/account-mappings', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'updateAccountMappings'])->name('update-account-mappings');
            Route::put('/accounting', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'updateAccounting'])->name('update-accounting');
            Route::put('/approval', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'updateApproval'])->name('update-approval');
            Route::put('/notifications', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'updateNotifications'])->name('update-notifications');
            Route::post('/export', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'exportSettings'])->name('export-settings');
            Route::post('/import', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'importSettings'])->name('import-settings');
            Route::get('/features', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'featuresIndex'])->name('features');
        });

        // Analytics
        Route::prefix('analytics')->name('analytics.')->middleware('feature:analytics')->group(function () {
            Route::get('financial-ratios', [\App\Http\Controllers\AnalyticsController::class, 'financialRatios'])->name('financial-ratios');
            Route::get('revenue-expense-trends', [\App\Http\Controllers\AnalyticsController::class, 'revenueExpenseTrends'])->name('revenue-expense-trends');
            Route::get('sales', [\App\Http\Controllers\AnalyticsController::class, 'sales'])->name('sales');
            Route::get('purchasing', [\App\Http\Controllers\AnalyticsController::class, 'purchasing'])->name('purchasing');
            Route::get('inventory', [\App\Http\Controllers\AnalyticsController::class, 'inventory'])->name('inventory');
            Route::get('profitability', [\App\Http\Controllers\AnalyticsController::class, 'profitability'])->name('profitability');
            // Budget vs Actual analytics route removed — will be rebuilt with budgeting module
            Route::get('cash-flow-trend', [\App\Http\Controllers\AnalyticsController::class, 'cashFlowTrend'])->name('cash-flow-trend');
        });

        // Business Intelligence (BI)
        Route::prefix('bi')->name('bi.')->middleware('feature:bi')->group(function () {
            Route::get('true-total-cost', [\App\Http\Controllers\BiController::class, 'trueTotalCost'])->name('true-total-cost');
            Route::get('customer-lifetime-value', [\App\Http\Controllers\BiController::class, 'customerLifetimeValue'])->name('customer-lifetime-value');
            Route::get('employee-productivity', [\App\Http\Controllers\BiController::class, 'employeeProductivity'])->name('employee-productivity');
            Route::get('branch-profitability', [\App\Http\Controllers\BiController::class, 'branchProfitability'])->name('branch-profitability');
        });

        //PDFS
        Route::middleware(['web', 'auth'])->prefix('pdf')->name('pdf.')->group(function () {
            Route::get('/{type}/{id}/preview',   [PdfController::class, 'preview'])->name('preview');
            Route::get('/{type}/{id}/download',  [PdfController::class, 'download'])->name('download');
        });

        // POS
        Route::prefix('pos')->name('pos.')->middleware('feature:pos')->group(function () {
            // Terminals
            Route::get('terminals', [\App\Http\Controllers\POS\PosTerminalController::class, 'index'])->name('terminals.index');
            Route::post('terminals', [\App\Http\Controllers\POS\PosTerminalController::class, 'store'])->name('terminals.store');
            Route::patch('terminals/{terminal}', [\App\Http\Controllers\POS\PosTerminalController::class, 'update'])->name('terminals.update');
            Route::patch('terminals/{terminal}/toggle', [\App\Http\Controllers\POS\PosTerminalController::class, 'toggle'])->name('terminals.toggle');

            // Payment Methods
            Route::get('payment-methods', [\App\Http\Controllers\POS\PosPaymentMethodController::class, 'index'])->name('payment-methods.index');
            Route::post('payment-methods', [\App\Http\Controllers\POS\PosPaymentMethodController::class, 'store'])->name('payment-methods.store');
            Route::patch('payment-methods/{paymentMethod}', [\App\Http\Controllers\POS\PosPaymentMethodController::class, 'update'])->name('payment-methods.update');
            Route::patch('payment-methods/{paymentMethod}/toggle', [\App\Http\Controllers\POS\PosPaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');

            // Till Sessions
            Route::get('till-sessions', [\App\Http\Controllers\POS\TillSessionController::class, 'index'])->name('till-sessions.index');
            Route::post('till-sessions/open', [\App\Http\Controllers\POS\TillSessionController::class, 'open'])->name('till-sessions.open');
            Route::post('till-sessions/{session}/close', [\App\Http\Controllers\POS\TillSessionController::class, 'close'])->name('till-sessions.close');
            Route::get('till-sessions/{session}', [\App\Http\Controllers\POS\TillSessionController::class, 'show'])->name('till-sessions.show');

            // POS Sales
            Route::get('sales/checkout', [\App\Http\Controllers\POS\PosSaleController::class, 'checkout'])->name('sales.checkout');
            Route::post('sales', [\App\Http\Controllers\POS\PosSaleController::class, 'store'])->name('sales.store');
            Route::get('sales/{id}/receipt', [\App\Http\Controllers\POS\PosSaleController::class, 'receipt'])->name('sales.receipt');
            Route::get('sales/{id}/lines-json', [\App\Http\Controllers\POS\PosSaleController::class, 'linesJson'])->name('sales.lines-json');
            Route::post('sales/sync-offline', [\App\Http\Controllers\POS\PosSaleController::class, 'syncOffline'])->name('sales.sync-offline');

            // Settlements
            Route::get('settlements', [\App\Http\Controllers\POS\PosSettlementController::class, 'index'])->name('settlements.index');
            Route::get('settlements/create', [\App\Http\Controllers\POS\PosSettlementController::class, 'create'])->name('settlements.create');
            Route::post('settlements', [\App\Http\Controllers\POS\PosSettlementController::class, 'store'])->name('settlements.store');
            Route::get('settlements/{settlement}', [\App\Http\Controllers\POS\PosSettlementController::class, 'show'])->name('settlements.show');

            // Returns / Refunds
            Route::get('returns', [\App\Http\Controllers\POS\PosReturnController::class, 'index'])->name('returns.index');
            Route::get('returns/create', [\App\Http\Controllers\POS\PosReturnController::class, 'create'])->name('returns.create');
            Route::post('returns', [\App\Http\Controllers\POS\PosReturnController::class, 'store'])->name('returns.store');
            Route::get('returns/{return}', [\App\Http\Controllers\POS\PosReturnController::class, 'show'])->name('returns.show');

            // POS Reports
            Route::get('reports/x-report', [\App\Http\Controllers\POS\PosReportController::class, 'xReport'])->name('reports.x-report');
            Route::get('reports/z-report', [\App\Http\Controllers\POS\PosReportController::class, 'zReport'])->name('reports.z-report');
            Route::get('reports/sales-by-terminal', [\App\Http\Controllers\POS\PosReportController::class, 'salesByTerminal'])->name('reports.sales-by-terminal');
            Route::get('reports/sales-by-cashier', [\App\Http\Controllers\POS\PosReportController::class, 'salesByCashier'])->name('reports.sales-by-cashier');

            // Cashier PIN login (accessible to any company user)
            Route::get('cashier/login', [\App\Http\Controllers\POS\PosCashierController::class, 'showLoginForm'])->name('cashier.login');
            Route::post('cashier/login', [\App\Http\Controllers\POS\PosCashierController::class, 'login'])->name('cashier.login.post');
            Route::post('cashier/logout', [\App\Http\Controllers\POS\PosCashierController::class, 'logout'])->name('cashier.logout');

            // EIS E-Invoicing
            Route::get('eis/terminals', [\App\Http\Controllers\POS\EisController::class, 'terminals'])->name('eis.terminals');
            Route::post('eis/terminals', [\App\Http\Controllers\POS\EisController::class, 'storeTerminal'])->name('eis.terminals.store');
            Route::post('eis/terminals/{terminal}/activate', [\App\Http\Controllers\POS\EisController::class, 'activateTerminal'])->name('eis.terminals.activate');
            Route::get('eis/submissions', [\App\Http\Controllers\POS\EisController::class, 'submissions'])->name('eis.submissions');
            Route::post('eis/submissions/{submission}/retry', [\App\Http\Controllers\POS\EisController::class, 'retrySubmission'])->name('eis.submissions.retry');
        });

        // POS Dashboard (requires cashier PIN session)
        Route::prefix('pos')->name('pos.')->middleware(['feature:pos', 'pos.cashier'])->group(function () {
            Route::get('dashboard', function () {
                return view('pos.dashboard');
            })->name('dashboard');
        });

        // Personal to-do list — tasks are strictly personal (scoped to the
        // current user at query level); available to every company user.
        Route::prefix('todo')->name('todo.')
            ->middleware('role_or_permission:system_admin|company_admin|accountant|approver|viewer|bookkeeper|cashier|auditor')
            ->group(function () {
                Route::get('/', [TodoTaskController::class, 'index'])->name('index');
                Route::get('/modal', [TodoTaskController::class, 'modal'])->name('modal');
                Route::post('/', [TodoTaskController::class, 'store'])->name('store');
                Route::put('/{task}', [TodoTaskController::class, 'update'])->name('update');
                Route::post('/{task}/complete', [TodoTaskController::class, 'complete'])->name('complete');
                Route::post('/{task}/reopen', [TodoTaskController::class, 'reopen'])->name('reopen');
                Route::delete('/{task}', [TodoTaskController::class, 'destroy'])->name('destroy');
            });

        // Personal favourites — star dropdown + pinnable sidebar, per user.
        Route::prefix('favourites')->name('favourites.')
            ->middleware('role_or_permission:system_admin|company_admin|accountant|approver|viewer|bookkeeper|cashier|auditor')
            ->group(function () {
                Route::get('/', [FavouritesController::class, 'index'])->name('index');
                Route::get('/pages', [FavouritesController::class, 'pages'])->name('pages');
                Route::post('/', [FavouritesController::class, 'store'])->name('store');
                Route::delete('/{pageKey}', [FavouritesController::class, 'destroy'])->name('destroy');
                Route::patch('/reorder', [FavouritesController::class, 'reorder'])->name('reorder');
                Route::patch('/preferences', [FavouritesController::class, 'preferences'])->name('preferences');
            });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // UI font-scale preference (A-/A+). Lives in the outer auth group (NOT the
    // tenant group) so it works from tenant pages and the super-admin panel alike;
    // stored on the central users table.
    Route::post('/preferences/font-scale', [UserPreferenceController::class, 'updateFontScale'])
        ->name('preferences.font-scale');
});

require __DIR__.'/auth.php';

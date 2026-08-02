<?php

use App\Http\Controllers\Accounting\AccountClassificationController;
use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AccountingPeriodController;
use App\Http\Controllers\Accounting\AgingReportController;
use App\Http\Controllers\Accounting\AssemblyController;
use App\Http\Controllers\Accounting\BalanceSheetController;
use App\Http\Controllers\Accounting\EquityStatementController;
use App\Http\Controllers\Accounting\BankController;
use App\Http\Controllers\Accounting\BillController;
use App\Http\Controllers\Accounting\CashFlowController;
use App\Http\Controllers\Accounting\CreditNoteController;
use App\Http\Controllers\Accounting\CostCenterController;
use App\Http\Controllers\Accounting\CustomerController;
use App\Http\Controllers\Accounting\CustomerPaymentController;
use App\Http\Controllers\Accounting\EmployeeController;
use App\Http\Controllers\Accounting\GeneralLedgerController;
use App\Http\Controllers\Accounting\FiscalYearController;
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
use App\Http\Controllers\Accounting\ReconciliationController;
use App\Http\Controllers\Accounting\StockAdjustmentController;
use App\Http\Controllers\Accounting\StockCountController;
use App\Http\Controllers\Accounting\StockTransferController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\VendorCentreController;
use App\Http\Controllers\Accounting\VendorController;
use App\Http\Controllers\Accounting\VendorCreditController;
use App\Http\Controllers\Accounting\VendorPaymentController;
use App\Http\Controllers\Accounting\PurchaseRequisitionController;
use App\Http\Controllers\Accounting\PurchaseOrderController;
use App\Http\Controllers\Accounting\GoodsReceivedNoteController;
use App\Http\Controllers\Accounting\QuotationController;
use App\Http\Controllers\Accounting\ReportCenterController;
use App\Http\Controllers\Accounting\SalesReceiptController;
use App\Http\Controllers\Accounting\SalesRegisterController;
use App\Http\Controllers\Accounting\MakeDepositController;
use App\Http\Controllers\Accounting\ChequeController;
use App\Http\Controllers\Accounting\PettyCashController;
use App\Http\Controllers\Accounting\CashPositionController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/companies', [CompanyController::class, 'index'])
        ->name('companies.index');

    Route::post('/companies', [CompanyController::class, 'store'])
        ->name('companies.store');

    Route::get('/companies/{id}/select', [CompanyController::class, 'select'])
        ->name('companies.select');

    Route::middleware(['company.context', 'company.active'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::patch('/companies/{company}', [CompanyController::class, 'update'])
            ->name('companies.update');

        Route::get('/branches', [BranchController::class, 'index'])
            ->name('branches.index');

        Route::post('/branches', [BranchController::class, 'store'])
            ->name('branches.store');

        Route::patch('/branches/{branch}', [BranchController::class, 'update'])
            ->name('branches.update');

        Route::patch('/branches/{branch}/toggle', [BranchController::class, 'toggle'])
            ->name('branches.toggle');

        Route::prefix('accounting')->name('accounting.')
            ->middleware('role_or_permission:system_admin|company_admin|accountant|approver|viewer')
            ->group(function () {
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
            Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::patch('customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');

            // Vendors
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
            Route::get('quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
            Route::post('quotations', [QuotationController::class, 'store'])->name('quotations.store');
            Route::get('quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
            Route::get('quotations/{quotation}/edit', [QuotationController::class, 'edit'])->name('quotations.edit');
            Route::put('quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update');
            Route::post('quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
            Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('quotations.accept')->middleware(['permission:quotations.approve', 'sod:quotation']);
            Route::post('quotations/{quotation}/decline', [QuotationController::class, 'decline'])->name('quotations.decline')->middleware(['permission:quotations.approve', 'sod:quotation']);
            Route::post('quotations/{quotation}/convert-to-invoice', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert-to-invoice')->middleware(['permission:quotations.convert', 'sod:quotation']);
            Route::post('quotations/{quotation}/convert-to-receipt', [QuotationController::class, 'convertToSalesReceipt'])->name('quotations.convert-to-receipt')->middleware(['permission:quotations.convert', 'sod:quotation']);
            Route::post('quotations/{quotation}/void', [QuotationController::class, 'void'])->name('quotations.void')->middleware(['permission:quotations.void', 'sod:quotation']);
            Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
            Route::post('quotations/{quotation}/email', [QuotationController::class, 'email'])->name('quotations.email');

            // ── Sales Receipts ──
            Route::get('sales-receipts', [SalesReceiptController::class, 'index'])->name('sales-receipts.index');
            Route::get('sales-receipts/create', [SalesReceiptController::class, 'create'])->name('sales-receipts.create');
            Route::post('sales-receipts', [SalesReceiptController::class, 'store'])->name('sales-receipts.store');
            Route::get('sales-receipts/{salesReceipt}', [SalesReceiptController::class, 'show'])->name('sales-receipts.show');
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
            Route::get('vendor-payments/create', [VendorPaymentController::class, 'create'])->name('vendor-payments.create');
            Route::post('vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store');
            Route::get('vendor-payments/{payment}', [VendorPaymentController::class, 'show'])->name('vendor-payments.show');

            // Expenses (immediate payment, no AP)
            Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
            Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
            Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
            Route::post('expenses/{expense}/post', [ExpenseController::class, 'post'])->name('expenses.post')->middleware(['permission:expenses.post', 'sod:expense']);
            Route::post('expenses/{expense}/void', [ExpenseController::class, 'void'])->name('expenses.void')->middleware(['permission:expenses.void', 'sod:expense']);

            // Vendor Centre
            Route::get('vendor-centre', [VendorCentreController::class, 'index'])->name('vendor-centre.index');
            Route::get('vendor-centre/{vendor}', [VendorCentreController::class, 'show'])->name('vendor-centre.show');

            Route::middleware('feature:purchasing')->group(function () {
            // Purchase Requisitions
                Route::get('purchase-requisitions', [PurchaseRequisitionController::class, 'index'])->name('purchase-requisitions.index');
                Route::get('purchase-requisitions/create', [PurchaseRequisitionController::class, 'create'])->name('purchase-requisitions.create');
                Route::post('purchase-requisitions', [PurchaseRequisitionController::class, 'store'])->name('purchase-requisitions.store');
                Route::get('purchase-requisitions/{purchaseRequisition}', [PurchaseRequisitionController::class, 'show'])->name('purchase-requisitions.show');
                Route::get('purchase-requisitions/{purchaseRequisition}/edit', [PurchaseRequisitionController::class, 'edit'])->name('purchase-requisitions.edit');
                Route::put('purchase-requisitions/{purchaseRequisition}', [PurchaseRequisitionController::class, 'update'])->name('purchase-requisitions.update');
                Route::post('purchase-requisitions/{purchaseRequisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('purchase-requisitions.submit')->middleware(['permission:purchase-requisitions.submit', 'sod:purchaseRequisition']);
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

            // Goods Received Notes
                Route::get('goods-received-notes', [GoodsReceivedNoteController::class, 'index'])->name('goods-received-notes.index');
                Route::get('goods-received-notes/create', [GoodsReceivedNoteController::class, 'create'])->name('goods-received-notes.create');
                Route::post('goods-received-notes', [GoodsReceivedNoteController::class, 'store'])->name('goods-received-notes.store');
                Route::get('goods-received-notes/{goodsReceivedNote}', [GoodsReceivedNoteController::class, 'show'])->name('goods-received-notes.show');
                Route::post('goods-received-notes/{goodsReceivedNote}/post', [GoodsReceivedNoteController::class, 'post'])->name('goods-received-notes.post')->middleware('sod:goodsReceivedNote');
            });

            Route::middleware('feature:banking')->group(function () {
            // Banking
                Route::get('bank-accounts', [BankController::class, 'index'])->name('bank-accounts.index');
                Route::get('bank-accounts/{bankAccountId}/register', [BankController::class, 'register'])->name('bank-accounts.register');
                Route::get('bank-accounts/transfer', [BankController::class, 'transferForm'])->name('bank-accounts.transfer-form');
                Route::post('bank-accounts/transfer', [BankController::class, 'transfer'])->name('bank-accounts.transfer');
                Route::get('bank-accounts/{bankAccountId}/manual', [BankController::class, 'manualTransactionForm'])->name('bank-accounts.manual-form');
                Route::post('bank-accounts/{bankAccountId}/manual', [BankController::class, 'storeManualTransaction'])->name('bank-accounts.store-manual');

            // Bank Reconciliation
                Route::get('bank-reconciliation/{bankAccountId}', [ReconciliationController::class, 'index'])->name('bank-reconciliation.index');
                Route::get('bank-reconciliation/{bankAccountId}/import', [ReconciliationController::class, 'importForm'])->name('bank-reconciliation.import-form');
                Route::post('bank-reconciliation/{bankAccountId}/import', [ReconciliationController::class, 'import'])->name('bank-reconciliation.import');
                Route::get('bank-reconciliation/{reconciliationId}', [ReconciliationController::class, 'show'])->name('bank-reconciliation.show');
                Route::get('bank-reconciliation/{reconciliationId}/suggest', [ReconciliationController::class, 'suggestMatches'])->name('bank-reconciliation.suggest');
                Route::post('bank-reconciliation/{reconciliationId}/match', [ReconciliationController::class, 'match'])->name('bank-reconciliation.match');
                Route::post('bank-reconciliation/{reconciliationId}/unmatch', [ReconciliationController::class, 'unmatch'])->name('bank-reconciliation.unmatch');
                Route::get('bank-reconciliation/{reconciliationId}/create-transaction', [ReconciliationController::class, 'createTransactionForm'])->name('bank-reconciliation.create-tx-form');
                Route::post('bank-reconciliation/{reconciliationId}/create-transaction', [ReconciliationController::class, 'createTransaction'])->name('bank-reconciliation.create-tx');
                Route::post('bank-reconciliation/{reconciliationId}/complete', [ReconciliationController::class, 'complete'])->name('bank-reconciliation.complete');

            // Make Deposits
                Route::get('deposits', [MakeDepositController::class, 'index'])->name('deposits.index');
                Route::get('deposits/create', [MakeDepositController::class, 'create'])->name('deposits.create');
                Route::post('deposits', [MakeDepositController::class, 'store'])->name('deposits.store');

            // Cheques
                Route::get('cheques', [ChequeController::class, 'index'])->name('cheques.index');
                Route::get('cheques/create', [ChequeController::class, 'create'])->name('cheques.create');
                Route::post('cheques', [ChequeController::class, 'store'])->name('cheques.store');
                Route::get('cheques/{chequeId}', [ChequeController::class, 'show'])->name('cheques.show');
                Route::post('cheques/{chequeId}/void', [ChequeController::class, 'voidCheque'])->name('cheques.void')->middleware(['permission:cheques.void', 'sod:chequeId']);
                Route::get('cheques-register', [ChequeController::class, 'register'])->name('cheques.register');

            // Petty Cash
                Route::get('petty-cash', [PettyCashController::class, 'index'])->name('petty-cash.index');
                Route::get('petty-cash/create', [PettyCashController::class, 'createFund'])->name('petty-cash.create-fund');
                Route::post('petty-cash', [PettyCashController::class, 'storeFund'])->name('petty-cash.store-fund');
                Route::get('petty-cash/{fundId}', [PettyCashController::class, 'show'])->name('petty-cash.show');
                Route::post('petty-cash/establish', [PettyCashController::class, 'establish'])->name('petty-cash.establish')->middleware(['permission:petty-cash.establish', 'sod:fundId']);
                Route::post('petty-cash/expense', [PettyCashController::class, 'recordExpense'])->name('petty-cash.expense')->middleware('permission:petty-cash.expense');
                Route::post('petty-cash/replenish', [PettyCashController::class, 'replenish'])->name('petty-cash.replenish')->middleware('permission:petty-cash.replenish');

            // Cash Position
                Route::get('cash-position', [CashPositionController::class, 'index'])->name('cash-position.index');
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

            Route::middleware('feature:budgets')->group(function () {
            // Budgets
                Route::resource('budgets', \App\Http\Controllers\Accounting\BudgetController::class);
                Route::get('budgets/{budget}/variance', [\App\Http\Controllers\Accounting\BudgetController::class, 'variance'])->name('budgets.variance');
                Route::post('budgets/{budget}/approve', [\App\Http\Controllers\Accounting\BudgetController::class, 'approve'])->name('budgets.approve')->middleware('sod:budget');
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
            Route::get('reports/bank-reconciliation-history', [\App\Http\Controllers\Accounting\ReportControllers\BankReconciliationHistoryController::class, 'index'])->name('reports.bank-reconciliation-history');
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
            Route::post('/features/{feature}/toggle', [\App\Http\Controllers\SystemSettings\SettingsController::class, 'featuresToggle'])->name('features.toggle');
        });

        // Analytics
        Route::prefix('analytics')->name('analytics.')->middleware('feature:analytics')->group(function () {
            Route::get('financial-ratios', [\App\Http\Controllers\AnalyticsController::class, 'financialRatios'])->name('financial-ratios');
            Route::get('revenue-expense-trends', [\App\Http\Controllers\AnalyticsController::class, 'revenueExpenseTrends'])->name('revenue-expense-trends');
            Route::get('sales', [\App\Http\Controllers\AnalyticsController::class, 'sales'])->name('sales');
            Route::get('purchasing', [\App\Http\Controllers\AnalyticsController::class, 'purchasing'])->name('purchasing');
            Route::get('inventory', [\App\Http\Controllers\AnalyticsController::class, 'inventory'])->name('inventory');
            Route::get('profitability', [\App\Http\Controllers\AnalyticsController::class, 'profitability'])->name('profitability');
            Route::get('budget-vs-actual-trend', [\App\Http\Controllers\AnalyticsController::class, 'budgetVsActualTrend'])->name('budget-vs-actual-trend');
            Route::get('cash-flow-trend', [\App\Http\Controllers\AnalyticsController::class, 'cashFlowTrend'])->name('cash-flow-trend');
        });

        // Business Intelligence (BI)
        Route::prefix('bi')->name('bi.')->middleware('feature:bi')->group(function () {
            Route::get('true-total-cost', [\App\Http\Controllers\BiController::class, 'trueTotalCost'])->name('true-total-cost');
            Route::get('customer-lifetime-value', [\App\Http\Controllers\BiController::class, 'customerLifetimeValue'])->name('customer-lifetime-value');
            Route::get('employee-productivity', [\App\Http\Controllers\BiController::class, 'employeeProductivity'])->name('employee-productivity');
            Route::get('branch-profitability', [\App\Http\Controllers\BiController::class, 'branchProfitability'])->name('branch-profitability');
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
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

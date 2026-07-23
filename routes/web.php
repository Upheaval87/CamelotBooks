<?php

use App\Http\Controllers\Accounting\AccountClassificationController;
use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AccountingPeriodController;
use App\Http\Controllers\Accounting\AgingReportController;
use App\Http\Controllers\Accounting\BalanceSheetController;
use App\Http\Controllers\Accounting\BankController;
use App\Http\Controllers\Accounting\BillController;
use App\Http\Controllers\Accounting\CashFlowController;
use App\Http\Controllers\Accounting\CreditNoteController;
use App\Http\Controllers\Accounting\CustomerController;
use App\Http\Controllers\Accounting\CustomerPaymentController;
use App\Http\Controllers\Accounting\GeneralLedgerController;
use App\Http\Controllers\Accounting\IncomeStatementController;
use App\Http\Controllers\Accounting\InvoiceController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\ProductController;
use App\Http\Controllers\Accounting\RecurringJournalController;
use App\Http\Controllers\Accounting\ReconciliationController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use App\Http\Controllers\Accounting\VendorController;
use App\Http\Controllers\Accounting\VendorCreditController;
use App\Http\Controllers\Accounting\VendorPaymentController;
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

        Route::prefix('accounting')->name('accounting.')->group(function () {
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
            Route::post('journal-entries/{journalEntry}/submit-for-approval', [JournalEntryController::class, 'submitForApproval'])->name('journal-entries.submit-for-approval');
            Route::post('journal-entries/{journalEntry}/approve', [JournalEntryController::class, 'approve'])->name('journal-entries.approve');
            Route::post('journal-entries/{journalEntry}/reject', [JournalEntryController::class, 'reject'])->name('journal-entries.reject');
            Route::post('journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entries.reverse');

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
            Route::post('periods/{period}/close', [AccountingPeriodController::class, 'close'])->name('periods.close');
            Route::post('periods/{period}/lock', [AccountingPeriodController::class, 'lock'])->name('periods.lock');
            Route::post('periods/{period}/reopen', [AccountingPeriodController::class, 'reopen'])->name('periods.reopen');

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

            // Products
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');

            // Sales Invoices
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
            Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
            Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
            Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
            Route::get('invoices/{invoice}/print', [InvoiceController::class, 'printPdf'])->name('invoices.print');

            // Credit Notes
            Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes.index');
            Route::get('credit-notes/create', [CreditNoteController::class, 'create'])->name('credit-notes.create');
            Route::post('credit-notes', [CreditNoteController::class, 'store'])->name('credit-notes.store');
            Route::get('credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('credit-notes.show');
            Route::post('credit-notes/{creditNote}/post', [CreditNoteController::class, 'post'])->name('credit-notes.post');
            Route::get('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'applyForm'])->name('credit-notes.apply-form');
            Route::post('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply'])->name('credit-notes.apply');
            Route::post('credit-notes/{creditNote}/void', [CreditNoteController::class, 'void'])->name('credit-notes.void');

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
            Route::post('bills/{bill}/post', [BillController::class, 'post'])->name('bills.post');
            Route::post('bills/{bill}/approve', [BillController::class, 'approve'])->name('bills.approve');
            Route::post('bills/{bill}/void', [BillController::class, 'void'])->name('bills.void');

            // Vendor Credits
            Route::get('vendor-credits', [VendorCreditController::class, 'index'])->name('vendor-credits.index');
            Route::get('vendor-credits/create', [VendorCreditController::class, 'create'])->name('vendor-credits.create');
            Route::post('vendor-credits', [VendorCreditController::class, 'store'])->name('vendor-credits.store');
            Route::get('vendor-credits/{vendorCredit}', [VendorCreditController::class, 'show'])->name('vendor-credits.show');
            Route::post('vendor-credits/{vendorCredit}/post', [VendorCreditController::class, 'post'])->name('vendor-credits.post');
            Route::get('vendor-credits/{vendorCredit}/apply', [VendorCreditController::class, 'applyForm'])->name('vendor-credits.apply-form');
            Route::post('vendor-credits/{vendorCredit}/apply', [VendorCreditController::class, 'apply'])->name('vendor-credits.apply');
            Route::post('vendor-credits/{vendorCredit}/void', [VendorCreditController::class, 'void'])->name('vendor-credits.void');

            // Vendor Payments
            Route::get('vendor-payments/create', [VendorPaymentController::class, 'create'])->name('vendor-payments.create');
            Route::post('vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store');
            Route::get('vendor-payments/{payment}', [VendorPaymentController::class, 'show'])->name('vendor-payments.show');

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

            // Financial Statements
            Route::get('income-statement', [IncomeStatementController::class, 'index'])->name('income-statement.index');
            Route::get('income-statement/export/csv', [IncomeStatementController::class, 'exportCsv'])->name('income-statement.export-csv');
            Route::get('income-statement/export/pdf', [IncomeStatementController::class, 'exportPdf'])->name('income-statement.export-pdf');

            Route::get('balance-sheet', [BalanceSheetController::class, 'index'])->name('balance-sheet.index');
            Route::get('balance-sheet/export/csv', [BalanceSheetController::class, 'exportCsv'])->name('balance-sheet.export-csv');
            Route::get('balance-sheet/export/pdf', [BalanceSheetController::class, 'exportPdf'])->name('balance-sheet.export-pdf');

            Route::get('cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');
            Route::get('cash-flow/export/csv', [CashFlowController::class, 'exportCsv'])->name('cash-flow.export-csv');
            Route::get('cash-flow/export/pdf', [CashFlowController::class, 'exportPdf'])->name('cash-flow.export-pdf');

            // Aging Reports
            Route::get('aging/ar-summary', [AgingReportController::class, 'arSummary'])->name('aging.ar-summary');
            Route::get('aging/ar-detail', [AgingReportController::class, 'arDetail'])->name('aging.ar-detail');
            Route::get('aging/ap-summary', [AgingReportController::class, 'apSummary'])->name('aging.ap-summary');
            Route::get('aging/ap-detail', [AgingReportController::class, 'apDetail'])->name('aging.ap-detail');
            Route::get('aging/export/csv', [AgingReportController::class, 'exportCsv'])->name('aging.export-csv');

            // Account Classification
            Route::get('account-classification', [AccountClassificationController::class, 'index'])->name('account-classification.index');
            Route::patch('account-classification/{account}', [AccountClassificationController::class, 'update'])->name('account-classification.update');
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

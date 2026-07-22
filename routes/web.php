<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AccountingPeriodController;
use App\Http\Controllers\Accounting\GeneralLedgerController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\RecurringJournalController;
use App\Http\Controllers\Accounting\TrialBalanceController;
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
            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('accounts/create', [AccountController::class, 'create'])->name('accounts.create');
            Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
            Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
            Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
            Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
            Route::patch('accounts/{account}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');

            Route::get('journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
            Route::get('journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
            Route::post('journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
            Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
            Route::post('journal-entries/{journalEntry}/submit-for-approval', [JournalEntryController::class, 'submitForApproval'])->name('journal-entries.submit-for-approval');
            Route::post('journal-entries/{journalEntry}/approve', [JournalEntryController::class, 'approve'])->name('journal-entries.approve');
            Route::post('journal-entries/{journalEntry}/reject', [JournalEntryController::class, 'reject'])->name('journal-entries.reject');
            Route::post('journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entries.reverse');

            Route::get('general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger.index');
            Route::get('general-ledger/export/csv', [GeneralLedgerController::class, 'exportCsv'])->name('general-ledger.export-csv');
            Route::get('general-ledger/{accountId}', [GeneralLedgerController::class, 'account'])->name('general-ledger.account');
            Route::get('general-ledger/{accountId}/export/csv', [GeneralLedgerController::class, 'exportCsv'])->name('general-ledger.account-export-csv');
            Route::get('general-ledger/{accountId}/export/pdf', [GeneralLedgerController::class, 'exportPdf'])->name('general-ledger.account-export-pdf');

            Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance.index');
            Route::get('trial-balance/export/csv', [TrialBalanceController::class, 'exportCsv'])->name('trial-balance.export-csv');
            Route::get('trial-balance/export/pdf', [TrialBalanceController::class, 'exportPdf'])->name('trial-balance.export-pdf');

            Route::get('periods', [AccountingPeriodController::class, 'index'])->name('periods.index');
            Route::post('periods', [AccountingPeriodController::class, 'store'])->name('periods.store');
            Route::post('periods/{period}/close', [AccountingPeriodController::class, 'close'])->name('periods.close');
            Route::post('periods/{period}/lock', [AccountingPeriodController::class, 'lock'])->name('periods.lock');
            Route::post('periods/{period}/reopen', [AccountingPeriodController::class, 'reopen'])->name('periods.reopen');

            Route::get('recurring-journals', [RecurringJournalController::class, 'index'])->name('recurring-journals.index');
            Route::get('recurring-journals/create', [RecurringJournalController::class, 'create'])->name('recurring-journals.create');
            Route::post('recurring-journals', [RecurringJournalController::class, 'store'])->name('recurring-journals.store');
            Route::get('recurring-journals/{template}', [RecurringJournalController::class, 'show'])->name('recurring-journals.show');
            Route::get('recurring-journals/{template}/edit', [RecurringJournalController::class, 'edit'])->name('recurring-journals.edit');
            Route::put('recurring-journals/{template}', [RecurringJournalController::class, 'update'])->name('recurring-journals.update');
            Route::patch('recurring-journals/{template}/toggle', [RecurringJournalController::class, 'toggle'])->name('recurring-journals.toggle');
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

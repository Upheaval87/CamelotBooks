<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Services\Accounting\BankService;
use App\Services\Accounting\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReconciliationService $service;

    protected BankService $bankService;

    protected Company $company;

    protected Account $bankAccount;

    protected Account $incomeAccount;

    protected AccountingPeriod $period;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReconciliationService::class);
        $this->bankService = app(BankService::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Interest Income',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    public function test_import_bank_statement_creates_statement_and_lines(): void
    {
        $csvContent = "date,description,amount\n2026-02-01,Opening Balance,5000.00\n2026-02-05,Deposit,1000.00\n2026-02-10,Bank Fee,-25.00\n";

        $import = $this->service->importStatement([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'statement_date' => '2026-02-28',
            'statement_end_balance' => 5975.00,
            'filename' => 'feb_statement.csv',
        ], $csvContent, $this->userId);

        $this->assertNotNull($import->id);
        $this->assertEquals('feb_statement.csv', $import->filename);
        $this->assertEquals(3, $import->line_count);

        $lines = $import->lines()->get();
        $this->assertCount(3, $lines);
    }

    public function test_matching_bank_tx_to_statement_line_marks_reconciled(): void
    {
        $deposit = $this->bankService->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'type' => 'deposit',
            'amount' => 1000,
            'date' => '2026-02-05',
            'description' => 'Customer payment',
            'credit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $csvContent = "date,description,amount\n2026-02-05,Customer payment,1000.00\n";

        $import = $this->service->importStatement([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'statement_date' => '2026-02-28',
            'statement_end_balance' => 1000,
            'filename' => 'feb_statement.csv',
        ], $csvContent, $this->userId);

        $statementLine = $import->lines()->first();

        $reconciliation = $this->service->startReconciliation(
            $this->bankAccount->id,
            $import->id,
            $this->company->id
        );

        $this->assertEquals(BankReconciliation::STATUS_IN_PROGRESS, $reconciliation->status);

        $this->service->matchItems($reconciliation->id, [
            [
                'bank_statement_line_id' => $statementLine->id,
                'bank_transaction_id' => $deposit->id,
                'amount' => 1000,
            ],
        ]);

        $this->assertEquals(1, $reconciliation->items()->count());

        $bankTx = BankTransaction::find($deposit->id);
        $this->assertFalse($bankTx->is_reconciled);

        $completed = $this->service->completeReconciliation($reconciliation->id, $this->userId);

        $this->assertEquals('completed', $completed->status);
        $this->assertNotNull($completed->completed_at);

        $bankTx->refresh();
        $this->assertTrue($bankTx->is_reconciled);
        $this->assertNotNull($bankTx->reconciled_at);
    }

    public function test_completed_reconciliation_updates_cleared_balance(): void
    {
        $deposit = $this->bankService->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'type' => 'deposit',
            'amount' => 2000,
            'date' => '2026-02-05',
            'description' => 'Deposit',
            'credit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $csvContent = "date,description,amount\n2026-02-05,Deposit,2000.00\n";

        $import = $this->service->importStatement([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'statement_date' => '2026-02-28',
            'statement_end_balance' => 2000,
            'filename' => 'feb_statement.csv',
        ], $csvContent, $this->userId);

        $statementLine = $import->lines()->first();

        $reconciliation = $this->service->startReconciliation(
            $this->bankAccount->id,
            $import->id,
            $this->company->id
        );

        $this->service->matchItems($reconciliation->id, [
            [
                'bank_statement_line_id' => $statementLine->id,
                'bank_transaction_id' => $deposit->id,
                'amount' => 2000,
            ],
        ]);

        $completed = $this->service->completeReconciliation($reconciliation->id, $this->userId);

        $this->assertEquals('completed', $completed->status);
        $this->assertEquals(2000.00, (float) $completed->cleared_balance);
    }

    public function test_suggest_matches_pairs_by_amount_and_date(): void
    {
        $deposit = $this->bankService->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'type' => 'deposit',
            'amount' => 1500,
            'date' => '2026-02-10',
            'description' => 'Wire transfer',
            'credit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $csvContent = "date,description,amount\n2026-02-10,Wire transfer from customer,1500.00\n";

        $import = $this->service->importStatement([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'statement_date' => '2026-02-28',
            'statement_end_balance' => 1500,
            'filename' => 'feb.csv',
        ], $csvContent, $this->userId);

        $reconciliation = $this->service->startReconciliation(
            $this->bankAccount->id,
            $import->id,
            $this->company->id
        );

        $suggestions = $this->service->suggestMatches($reconciliation->id);

        $this->assertNotEmpty($suggestions);
        $this->assertEquals($deposit->id, $suggestions[0]['bank_transaction_id']);
        $this->assertEquals($import->lines()->first()->id, $suggestions[0]['bank_statement_line_id']);
    }
}

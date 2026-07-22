<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Services\Accounting\BankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BankServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BankService $service;

    protected Company $company;

    protected Account $bankAccount1;

    protected Account $bankAccount2;

    protected Account $incomeAccount;

    protected AccountingPeriod $period;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BankService::class);

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

        $this->bankAccount1 = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $this->bankAccount2 = Account::create([
            'company_id' => $this->company->id,
            'code' => '1020',
            'name' => 'Savings Account',
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

    public function test_transfer_between_accounts_creates_balanced_je(): void
    {
        $result = $this->service->transfer(
            $this->bankAccount1->id,
            $this->bankAccount2->id,
            5000,
            '2026-02-15',
            'Transfer to savings',
            $this->company->id,
            $this->userId
        );

        [$sourceTx, $targetTx] = $result;

        $this->assertEquals(-5000.00, (float) $sourceTx->amount);
        $this->assertEquals(5000.00, (float) $targetTx->amount);
        $this->assertNotNull($sourceTx->journal_entry_id);
        $this->assertEquals($sourceTx->journal_entry_id, $targetTx->journal_entry_id);

        $je = JournalEntry::find($sourceTx->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);

        $lines = $je->lines()->get();
        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));
    }

    public function test_create_manual_deposit(): void
    {
        $tx = $this->service->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount1->id,
            'type' => 'deposit',
            'amount' => 1000,
            'date' => '2026-02-15',
            'description' => 'Interest earned',
            'credit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $this->assertInstanceOf(BankTransaction::class, $tx);
        $this->assertEquals(1000.00, (float) $tx->amount);
        $this->assertEquals('deposit', $tx->type);

        $bankTx = BankTransaction::find($tx->id);
        $this->assertNotNull($bankTx->journal_entry_id);

        $je = JournalEntry::find($bankTx->journal_entry_id);
        $this->assertNotNull($je);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);

        $lines = $je->lines()->get();
        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));
    }

    public function test_create_manual_withdrawal(): void
    {
        $tx = $this->service->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount1->id,
            'type' => 'withdrawal',
            'amount' => 200,
            'date' => '2026-02-15',
            'description' => 'Bank fee',
            'debit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $bankTx = BankTransaction::find($tx->id);
        $this->assertEquals(-200.00, (float) $bankTx->amount);
        $this->assertEquals('withdrawal', $bankTx->type);
    }

    public function test_transfer_to_non_bank_account_fails(): void
    {
        $regularAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a bank account');

        $this->service->transfer(
            $this->bankAccount1->id,
            $regularAccount->id,
            1000,
            '2026-02-15',
            'test',
            $this->company->id,
            $this->userId
        );
    }

    public function test_register_returns_sorted_transactions_with_running_balance(): void
    {
        $this->service->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount1->id,
            'type' => 'deposit',
            'amount' => 1000,
            'date' => '2026-02-10',
            'description' => 'Deposit 1',
            'credit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $this->service->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount1->id,
            'type' => 'deposit',
            'amount' => 500,
            'date' => '2026-02-15',
            'description' => 'Deposit 2',
            'credit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $this->service->createManualTransaction([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount1->id,
            'type' => 'withdrawal',
            'amount' => 200,
            'date' => '2026-02-20',
            'description' => 'Fee',
            'debit_account_id' => $this->incomeAccount->id,
        ], $this->userId);

        $register = $this->service->getRegister($this->bankAccount1->id, $this->company->id);

        $this->assertEquals(3, $register->count());

        $lastTx = $register->last();
        $this->assertEquals(1300.00, (float) $lastTx->running_balance);
    }

    public function test_bank_account_validates_is_bank_account_flag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a bank account');

        $this->service->transfer(
            $this->incomeAccount->id,
            $this->bankAccount1->id,
            100,
            '2026-02-15',
            'test',
            $this->company->id,
            $this->userId
        );
    }
}

<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Cheque;
use App\Models\Company;
use App\Services\Accounting\ChequeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChequeTest extends TestCase
{
    use RefreshDatabase;

    protected ChequeService $service;
    protected Company $company;
    protected Account $bankAccount;
    protected Account $expenseAccount;
    protected AccountingPeriod $period;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ChequeService::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Full Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
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
            'next_cheque_number' => 1,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating',
            'is_active' => true,
        ]);
    }

    public function test_write_cheque_creates_cheque_with_sequential_number(): void
    {
        $cheque = $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-15',
            'payee' => 'Landlord Inc.',
            'amount' => 1500.00,
            'debit_account_id' => $this->expenseAccount->id,
            'memo' => 'February rent',
        ], $this->userId);

        $this->assertNotNull($cheque->id);
        $this->assertEquals(1, $cheque->cheque_number);
        $this->assertEquals(1500.00, (float) $cheque->amount);
        $this->assertEquals('outstanding', $cheque->status);
        $this->assertEquals('Landlord Inc.', $cheque->payee);
        $this->assertNotNull($cheque->journal_entry_id);
    }

    public function test_write_cheque_increments_sequential_number(): void
    {
        $cheque1 = $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-15',
            'payee' => 'Vendor A',
            'amount' => 100.00,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);

        $cheque2 = $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-16',
            'payee' => 'Vendor B',
            'amount' => 200.00,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);

        $this->assertEquals(1, $cheque1->cheque_number);
        $this->assertEquals(2, $cheque2->cheque_number);
    }

    public function test_write_cheque_posts_correct_journal_entry(): void
    {
        $cheque = $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-15',
            'payee' => 'Landlord Inc.',
            'amount' => 1500.00,
            'debit_account_id' => $this->expenseAccount->id,
            'memo' => 'February rent',
        ], $this->userId);

        $je = $cheque->journalEntry;
        $this->assertNotNull($je);
        $this->assertEquals('posted', $je->status);
        $this->assertEquals('cheque', $je->source_module);

        $lines = $je->lines()->get();
        $this->assertCount(2, $lines);

        $debitLine = $lines->first(fn($l) => $l->debit > 0);
        $creditLine = $lines->first(fn($l) => $l->credit > 0);

        $this->assertEquals($this->expenseAccount->id, $debitLine->account_id);
        $this->assertEquals(1500.00, (float) $debitLine->debit);

        $this->assertEquals($this->bankAccount->id, $creditLine->account_id);
        $this->assertEquals(1500.00, (float) $creditLine->credit);
    }

    public function test_void_cheque_reverses_and_marks_void(): void
    {
        $cheque = $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-15',
            'payee' => 'Vendor',
            'amount' => 500.00,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);

        $bankTxId = $cheque->source_id;

        $voidedCheque = $this->service->voidCheque($cheque, $this->userId);

        $this->assertEquals('void', $voidedCheque->status);
        $this->assertNotNull($voidedCheque->voided_at);

        $bankTx = \App\Models\BankTransaction::find($bankTxId);
        $this->assertNull($bankTx);
    }

    public function test_get_register_returns_cheques(): void
    {
        $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-15',
            'payee' => 'Vendor A',
            'amount' => 100.00,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);

        $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-03-01',
            'payee' => 'Vendor B',
            'amount' => 200.00,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);

        $register = $this->service->getRegister($this->company->id);
        $this->assertCount(2, $register);

        $filtered = $this->service->getRegister($this->company->id, null, '2026-03-01');
        $this->assertCount(1, $filtered);
    }

    public function test_write_cheque_fails_with_zero_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('amount must be greater than zero');

        $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-15',
            'payee' => 'Vendor',
            'amount' => 0,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);
    }

    public function test_write_cheque_fails_with_invalid_bank_account(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => 9999,
            'date' => '2026-02-15',
            'payee' => 'Vendor',
            'amount' => 100.00,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);
    }

    public function test_double_void_fails(): void
    {
        $cheque = $this->service->writeCheque([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-15',
            'payee' => 'Vendor',
            'amount' => 500.00,
            'debit_account_id' => $this->expenseAccount->id,
        ], $this->userId);

        $this->service->voidCheque($cheque, $this->userId);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->voidCheque($cheque->fresh(), $this->userId);
    }
}

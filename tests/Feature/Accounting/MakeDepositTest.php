<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Accounting\BankService;
use App\Services\Accounting\MakeDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeDepositTest extends TestCase
{
    use RefreshDatabase;

    protected MakeDepositService $service;
    protected BankService $bankService;
    protected Company $company;
    protected Account $bankAccount;
    protected Account $undepositedAccount;
    protected Account $incomeAccount;
    protected AccountingPeriod $period;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MakeDepositService::class);
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
        ]);

        $this->undepositedAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1050',
            'name' => 'Undeposited Funds',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    protected function createUndepositedEntry(float $amount, string $date): JournalEntry
    {
        return app(\App\Services\Accounting\JournalPostingEngine::class)->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => $date,
            'source_module' => 'test',
            'memo' => "Test entry {$amount}",
            'lines' => [
                [
                    'account_id' => $this->undepositedAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => 'Debit undeposited',
                ],
                [
                    'account_id' => $this->incomeAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => 'Credit income',
                ],
            ],
        ]);
    }

    public function test_get_undeposited_funds_lines_returns_lines(): void
    {
        $je = $this->createUndepositedEntry(1000.00, '2026-02-01');

        $lines = $this->service->getUndepositedFundsLines($this->company->id);

        $this->assertCount(1, $lines);
        $this->assertEquals(1000.00, (float) $lines->first()->debit);
    }

    public function test_create_deposit_posts_journal_entry_and_bank_transaction(): void
    {
        $je = $this->createUndepositedEntry(2000.00, '2026-02-01');

        $bankTx = $this->service->createDeposit([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-05',
            'amount' => 2000.00,
            'description' => 'Daily deposit',
            'journal_entry_ids' => [$je->id],
        ], $this->userId);

        $this->assertNotNull($bankTx->id);
        $this->assertEquals(2000.00, (float) $bankTx->amount);
        $this->assertEquals('deposit', $bankTx->type);
        $this->assertEquals('make_deposit', $bankTx->source_type);

        $depositJe = $bankTx->journalEntry;
        $this->assertEquals('posted', $depositJe->status);
        $this->assertEquals('make_deposit', $depositJe->source_module);

        $lines = $depositJe->lines()->get();
        $this->assertCount(2, $lines);
    }

    public function test_get_undeposited_funds_balance(): void
    {
        $this->createUndepositedEntry(1500.00, '2026-02-01');
        $this->createUndepositedEntry(500.00, '2026-02-02');

        $balance = $this->service->getUndepositedFundsBalance($this->company->id);
        $this->assertEquals(2000.00, $balance);
    }

    public function test_create_deposit_fails_with_mismatched_amount(): void
    {
        $je = $this->createUndepositedEntry(1000.00, '2026-02-01');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not match deposit amount');

        $this->service->createDeposit([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-05',
            'amount' => 500.00,
            'journal_entry_ids' => [$je->id],
        ], $this->userId);
    }

    public function test_create_deposit_fails_with_invalid_bank_account(): void
    {
        $je = $this->createUndepositedEntry(1000.00, '2026-02-01');

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createDeposit([
            'company_id' => $this->company->id,
            'bank_account_id' => 9999,
            'date' => '2026-02-05',
            'amount' => 1000.00,
            'journal_entry_ids' => [$je->id],
        ], $this->userId);
    }

    public function test_deposited_items_excluded_from_available_lines(): void
    {
        $je = $this->createUndepositedEntry(1000.00, '2026-02-01');

        $this->service->createDeposit([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-05',
            'amount' => 1000.00,
            'journal_entry_ids' => [$je->id],
        ], $this->userId);

        $lines = $this->service->getUndepositedFundsLines($this->company->id);
        $this->assertCount(0, $lines);
    }

    public function test_create_deposit_fails_with_zero_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be greater than zero');

        $this->service->createDeposit([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-05',
            'amount' => 0,
            'journal_entry_ids' => [1],
        ], $this->userId);
    }
}

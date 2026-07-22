<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PostingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected JournalPostingEngine $engine;

    protected Company $company;

    protected Company $otherCompany;

    protected Account $debitAccount;

    protected Account $creditAccount;

    protected AccountingPeriod $period;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(JournalPostingEngine::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        $this->otherCompany = Company::create([
            'name' => 'Other Company',
            'company_code' => 'OTHER',
            'is_active' => true,
        ]);

        $this->debitAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $this->creditAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Revenue',
            'type' => 'income',
            'sub_type' => 'operating_income',
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);
    }

    public function test_unbalanced_entry_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debits and credits must be equal');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 500,
                ],
            ],
        ]);
    }

    public function test_cross_company_account_references_are_rejected(): void
    {
        $otherCompanyAccount = Account::create([
            'company_id' => $this->otherCompany->id,
            'code' => '5000',
            'name' => 'Other Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not belong to company');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $otherCompanyAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ]);
    }

    public function test_posted_entry_updates_account_balances(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ]);

        $this->assertEquals(JournalEntry::STATUS_POSTED, $entry->status);

        $this->debitAccount->refresh();
        $this->creditAccount->refresh();

        $this->assertEquals(1000.00, (float) $this->debitAccount->current_balance);
        $this->assertEquals(1000.00, (float) $this->creditAccount->current_balance);
    }

    public function test_draft_entry_does_not_affect_balances(): void
    {
        $entry = $this->engine->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 500,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 500,
                ],
            ],
        ]);

        $this->assertEquals(JournalEntry::STATUS_DRAFT, $entry->status);

        $this->debitAccount->refresh();
        $this->creditAccount->refresh();

        $this->assertEquals(0.00, (float) $this->debitAccount->current_balance);
        $this->assertEquals(0.00, (float) $this->creditAccount->current_balance);
    }

    public function test_entry_for_locked_period_is_rejected(): void
    {
        $this->period->update(['status' => 'locked']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is locked');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 100,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 100,
                ],
            ],
        ]);
    }

    public function test_reversing_an_entry_creates_correct_reversal(): void
    {
        $original = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 750,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 750,
                ],
            ],
        ]);

        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);

        $reversal = $this->engine->reverse($original->id, $this->userId, '2026-03-01');

        $original->refresh();

        $this->assertEquals(JournalEntry::STATUS_REVERSED, $original->status);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $reversal->status);
        $this->assertEquals('Reversal of ' . $original->journal_number, $reversal->memo);
        $this->assertEquals('reversal', $reversal->source_module);
        $this->assertEquals($original->id, $reversal->linked_entry_id);
        $this->assertEquals('2026-03-01', $reversal->date->format('Y-m-d'));

        $reversalLines = $reversal->lines->sortBy('id')->values();
        $originalLines = $original->lines->sortBy('id')->values();

        $this->assertEquals($originalLines[0]->account_id, $reversalLines[0]->account_id);
        $this->assertEquals($originalLines[0]->debit, $reversalLines[0]->credit);
        $this->assertEquals($originalLines[0]->credit, $reversalLines[0]->debit);

        $this->assertEquals($originalLines[1]->account_id, $reversalLines[1]->account_id);
        $this->assertEquals($originalLines[1]->debit, $reversalLines[1]->credit);
        $this->assertEquals($originalLines[1]->credit, $reversalLines[1]->debit);

        $this->debitAccount->refresh();
        $this->creditAccount->refresh();

        $this->assertEquals(0.00, (float) $this->debitAccount->current_balance);
        $this->assertEquals(0.00, (float) $this->creditAccount->current_balance);
    }
}

<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalPostingEngine;
use BadMethodCallException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LedgerIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected JournalPostingEngine $engine;

    protected Company $company;

    protected Account $cashAccount;

    protected Account $revenueAccount;

    protected Account $expenseAccount;

    protected Account $retainedEarnings;

    protected AccountingPeriod $period;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(JournalPostingEngine::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Integrity Test Co',
            'company_code' => 'INTG',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->revenueAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Salary Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->retainedEarnings = Account::create([
            'company_id' => $this->company->id,
            'code' => '3100',
            'name' => 'Retained Earnings',
            'type' => 'equity',
            'sub_type' => 'equity',
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

    public function test_unbalanced_entry_is_rejected_at_service_layer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debits and credits must be equal');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_entry_lines', 0);
    }

    public function test_posted_entry_cannot_be_updated(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('immutable');

        $entry->update(['memo' => 'Should not work']);
    }

    public function test_posted_entry_cannot_be_deleted(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('immutable');

        $entry->delete();
    }

    public function test_posted_entry_cannot_have_status_changed(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->expectException(BadMethodCallException::class);

        $entry->update(['status' => 'draft']);
    }

    public function test_reversed_entry_cannot_be_modified(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->engine->reverse($entry->id, $this->userId, '2026-03-01');

        $entry->refresh();

        $this->expectException(BadMethodCallException::class);

        $entry->update(['memo' => 'Should not work on reversed entry']);
    }

    public function test_reversed_entry_cannot_be_deleted(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->engine->reverse($entry->id, $this->userId, '2026-03-01');

        $entry->refresh();

        $this->expectException(BadMethodCallException::class);

        $entry->delete();
    }

    public function test_balance_is_computed_from_ledger_entries(): void
    {
        $this->assertEquals(0.0, $this->cashAccount->current_balance);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $this->assertEquals(1000.0, $this->cashAccount->current_balance);
        $this->assertEquals(1000.0, $this->revenueAccount->current_balance);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-20',
            'lines' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 300, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 300],
            ],
        ]);

        $this->assertEquals(700.0, $this->cashAccount->current_balance);
        $this->assertEquals(300.0, $this->expenseAccount->current_balance);
    }

    public function test_draft_entries_do_not_affect_computed_balance(): void
    {
        $this->engine->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->assertEquals(0.0, $this->cashAccount->current_balance);
        $this->assertEquals(0.0, $this->revenueAccount->current_balance);
    }

    public function test_reversal_correctly_adjusts_computed_balance(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 800, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 800],
            ],
        ]);

        $this->assertEquals(800.0, $this->cashAccount->current_balance);

        $this->engine->reverse($entry->id, $this->userId, '2026-03-01');

        $this->assertEquals(0.0, $this->cashAccount->current_balance);
        $this->assertEquals(0.0, $this->revenueAccount->current_balance);
    }

    public function test_posting_into_closed_period_is_rejected(): void
    {
        $this->period->update(['status' => 'closed']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is closed');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_posting_into_locked_period_is_rejected(): void
    {
        $this->period->update(['status' => 'locked']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is locked');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_ledger_integrity_check_passes_with_balanced_entries(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-20',
            'lines' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $result = JournalPostingEngine::verifyLedgerIntegrity($this->company->id);

        $this->assertTrue($result['is_balanced']);
        $this->assertEquals(0.0, $result['difference']);
        $this->assertEquals(1500.0, $result['total_debit']);
        $this->assertEquals(1500.0, $result['total_credit']);
    }

    public function test_ledger_integrity_only_counts_posted_entries(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $this->engine->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-20',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $result = JournalPostingEngine::verifyLedgerIntegrity($this->company->id);

        $this->assertTrue($result['is_balanced']);
        $this->assertEquals(1000.0, $result['total_debit']);
        $this->assertEquals(1000.0, $result['total_credit']);
    }

    public function test_period_close_generates_closing_entries_for_revenue_and_expenses(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-01-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
        ]);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-10',
            'lines' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 3000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 3000],
            ],
        ]);

        $this->assertEquals(10000.0, $this->revenueAccount->current_balance);
        $this->assertEquals(3000.0, $this->expenseAccount->current_balance);

        $closingEntry = $this->engine->closePeriod($this->period, $this->userId);

        $this->assertNotNull($closingEntry);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $closingEntry->status);
        $this->assertEquals('period_close', $closingEntry->source_module);
        $this->assertEquals($this->period->end_date->format('Y-m-d'), $closingEntry->date->format('Y-m-d'));

        $this->assertEquals(0.0, $this->revenueAccount->current_balance);
        $this->assertEquals(0.0, $this->expenseAccount->current_balance);
        $this->assertEquals(7000.0, $this->retainedEarnings->current_balance);

        $this->period->refresh();
        $this->assertEquals('closed', $this->period->status);
    }

    public function test_period_close_rejects_if_draft_entries_exist(): void
    {
        $this->engine->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('draft');

        $this->engine->closePeriod($this->period, $this->userId);
    }

    public function test_period_close_with_no_activity_closes_cleanly(): void
    {
        $closingEntry = $this->engine->closePeriod($this->period, $this->userId);

        $this->assertNull($closingEntry);

        $this->period->refresh();
        $this->assertEquals('closed', $this->period->status);
    }

    public function test_posted_entry_persists_after_balance_verification(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 2500, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 2500],
            ],
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'status' => 'posted',
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $entry->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 2500,
        ]);

        $this->assertEquals(2500.0, $this->cashAccount->current_balance);
        $this->assertEquals(2500.0, $this->revenueAccount->current_balance);

        $result = JournalPostingEngine::verifyLedgerIntegrity($this->company->id);
        $this->assertTrue($result['is_balanced']);
    }

    public function test_normal_balance_side_is_enforced(): void
    {
        $this->assertTrue($this->cashAccount->isDebitNormal());
        $this->assertFalse($this->cashAccount->isCreditNormal());

        $this->assertTrue($this->revenueAccount->isCreditNormal());
        $this->assertFalse($this->revenueAccount->isDebitNormal());

        $this->assertTrue($this->expenseAccount->isDebitNormal());
        $this->assertFalse($this->expenseAccount->isCreditNormal());

        $this->assertTrue($this->retainedEarnings->isCreditNormal());
        $this->assertFalse($this->retainedEarnings->isDebitNormal());
    }
}

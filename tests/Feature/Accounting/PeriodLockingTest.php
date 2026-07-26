<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PeriodLockingTest extends TestCase
{
    use RefreshDatabase;

    protected JournalPostingEngine $engine;

    protected Company $company;

    protected Account $debitAccount;

    protected Account $creditAccount;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(JournalPostingEngine::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TLOCK',
            'is_active' => true,
        ]);

        $this->debitAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->creditAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Revenue',
            'type' => 'income',
            'sub_type' => 'operating_income',
            'is_active' => true,
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    public function test_journal_entry_cannot_be_posted_with_date_inside_locked_period(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2025 Q4',
            'start_date' => '2025-10-01',
            'end_date' => '2025-12-31',
            'status' => 'locked',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is locked');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2025-11-15',
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_journal_entry_can_be_posted_with_date_inside_open_period(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 250, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 250],
            ],
        ]);

        $this->assertEquals('posted', $entry->status);
        $this->assertDatabaseHas('journal_entries', ['id' => $entry->id, 'status' => 'posted']);

        $this->debitAccount->refresh();
        $this->creditAccount->refresh();

        $this->assertEquals(250.00, (float) $this->debitAccount->current_balance);
        $this->assertEquals(250.00, (float) $this->creditAccount->current_balance);
    }

    public function test_locked_period_enforcement_at_engine_level_not_just_ui(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2025 Q4',
            'start_date' => '2025-10-01',
            'end_date' => '2025-12-31',
            'status' => 'locked',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2025-12-25',
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);
    }

    public function test_draft_entries_are_also_rejected_for_locked_periods(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2025 Q3',
            'start_date' => '2025-07-01',
            'end_date' => '2025-09-30',
            'status' => 'locked',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is locked');

        $this->engine->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2025-08-15',
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_closed_period_also_rejects_entries(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2025 Q4',
            'start_date' => '2025-10-01',
            'end_date' => '2025-12-31',
            'status' => 'closed',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is closed');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2025-11-01',
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_date_outside_any_period_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No accounting period found');

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2030-06-15',
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_posting_engine_rejects_locked_period_before_creating_entry(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2025 Q2',
            'start_date' => '2025-04-01',
            'end_date' => '2025-06-30',
            'status' => 'locked',
        ]);

        $entryCountBefore = \App\Models\JournalEntry::count();

        try {
            $this->engine->post([
                'company_id' => $this->company->id,
                'created_by' => $this->userId,
                'date' => '2025-05-01',
                'lines' => [
                    ['account_id' => $this->debitAccount->id, 'debit' => 200, 'credit' => 0],
                    ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 200],
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('locked', $e->getMessage());
        }

        $this->assertEquals($entryCountBefore, \App\Models\JournalEntry::count());
    }
}

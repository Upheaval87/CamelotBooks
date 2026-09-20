<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected User $poster;

    protected Account $debitAccount;

    protected Account $creditAccount;

    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'Journal Register Co',
            'company_code' => 'JREG',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        // A second user for post actions: SOD forbids the creator from posting.
        $this->poster = User::factory()->create();
        $this->poster->companies()->attach($this->company->id, ['role' => 'accountant']);
        $this->poster->assignRole('accountant');

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

        $this->costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'code' => 'CC-01',
            'name' => 'Operations',
            'is_active' => true,
        ]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026-08',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
        ]);

        // Covers "today" so the This Month period filter has live data.
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    protected function draftEntry(array $overrides = []): JournalEntry
    {
        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);

        return $engine->postAsDraft(array_merge([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-08-10',
            'memo' => 'Draft register entry',
            'branch_id' => null,
            'is_adjusting_entry' => false,
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 500,
                    'credit' => 0,
                    'cost_center_id' => $this->costCenter->id,
                    'memo' => 'Dr leg',
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 500,
                ],
            ],
        ], $overrides));
    }

    protected function postedEntry(array $overrides = []): JournalEntry
    {
        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);

        return $engine->post(array_merge([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-08-12',
            'memo' => 'Posted register entry',
            'branch_id' => null,
            'is_adjusting_entry' => false,
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 300,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 300,
                ],
            ],
        ], $overrides));
    }

    public function test_index_renders_register_markup(): void
    {
        $response = $this->get(route('accounting.journal-entries.index'));

        $response->assertOk();
        $response->assertSee('jr-wrap', false);
        $response->assertSee('jr-tabs', false);
        $response->assertSee('jr-table', false);
        $response->assertSee('journalRegister(', false);
        $response->assertSee('Unfinalized');
        $response->assertSee('Unposted · Finalized');
        $response->assertSee('New Journal');
        $response->assertSee('All types');
    }

    public function test_status_tab_filters_entries(): void
    {
        $draft = $this->draftEntry();
        $posted = $this->postedEntry();

        $this->get(route('accounting.journal-entries.index', ['status' => 'unfinalized']))
            ->assertOk()
            ->assertSee($draft->journal_number)
            ->assertDontSee($posted->journal_number);

        $this->get(route('accounting.journal-entries.index', ['status' => 'posted']))
            ->assertOk()
            ->assertSee($posted->journal_number)
            ->assertDontSee($draft->journal_number);
    }

    public function test_type_filter_uses_classifier_general_complement(): void
    {
        $manual = $this->draftEntry(['memo' => 'Manual posting']);
        $payroll = $this->draftEntry(['memo' => 'Payroll run', 'source_module' => 'payroll']);

        $this->get(route('accounting.journal-entries.index', ['type' => 'Payroll']))
            ->assertOk()
            ->assertSee($payroll->journal_number)
            ->assertDontSee($manual->journal_number);

        $this->get(route('accounting.journal-entries.index', ['type' => 'General']))
            ->assertOk()
            ->assertSee($manual->journal_number)
            ->assertDontSee($payroll->journal_number);
    }

    public function test_search_filter_matches_number_and_empty_state(): void
    {
        $draft = $this->draftEntry();

        $this->get(route('accounting.journal-entries.index', ['search' => $draft->journal_number]))
            ->assertOk()
            ->assertSee($draft->journal_number);

        $this->get(route('accounting.journal-entries.index', ['search' => 'ZZZ-no-match']))
            ->assertOk()
            ->assertSee('No journals match the current filters.');
    }

    public function test_period_mode_resolves_range(): void
    {
        $entry = $this->draftEntry(['date' => now()->toDateString(), 'memo' => 'This month entry']);

        $this->get(route('accounting.journal-entries.index', ['mode' => 'period', 'period' => 'this_month']))
            ->assertOk()
            ->assertSee($entry->journal_number)
            ->assertSee('value="this_month"', false);

        $this->get(route('accounting.journal-entries.index', ['mode' => 'period', 'period' => 'last_month']))
            ->assertOk()
            ->assertDontSee($entry->journal_number);
    }

    public function test_finalize_moves_draft_to_unposted_and_audits(): void
    {
        $draft = $this->draftEntry();

        $response = $this->post(route('accounting.journal-entries.finalize', $draft));

        $response->assertRedirect(route('accounting.journal-entries.index'));
        $response->assertSessionHas('success');

        $this->assertSame(
            JournalEntry::STATUS_PENDING_APPROVAL,
            $draft->fresh()->status
        );

        $this->assertDatabaseHas('account_audit_logs', [
            'journalable_type' => JournalEntry::class,
            'journalable_id' => $draft->id,
            'action' => 'finalized',
        ]);

        // Finalize must never be the ledger write.
        $this->assertNull($draft->fresh()->posted_at);
    }

    public function test_finalize_saves_edited_lines(): void
    {
        $draft = $this->draftEntry();

        $response = $this->post(route('accounting.journal-entries.finalize', $draft), [
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 900, 'credit' => 0, 'memo' => 'Edited Dr'],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 900, 'memo' => 'Edited Cr'],
            ],
        ]);

        $response->assertRedirect(route('accounting.journal-entries.index'));

        $draft->refresh();
        $this->assertSame(JournalEntry::STATUS_PENDING_APPROVAL, $draft->status);
        $this->assertCount(2, $draft->lines);
        $this->assertEquals(900, (float) $draft->lines->sum('debit'));
        $this->assertSame('Edited Dr', $draft->lines->first(fn ($line) => (float) $line->debit > 0)->memo);
    }

    public function test_finalize_rejects_unbalanced_lines(): void
    {
        $draft = $this->draftEntry();

        $response = $this->post(route('accounting.journal-entries.finalize', $draft), [
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 900, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 400],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(JournalEntry::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_finalize_blocked_for_non_creator(): void
    {
        $draft = $this->draftEntry();

        $response = $this->actingAs($this->poster)
            ->post(route('accounting.journal-entries.finalize', $draft));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(JournalEntry::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_post_writes_entry_to_ledger(): void
    {
        $draft = $this->draftEntry();
        $this->post(route('accounting.journal-entries.finalize', $draft))->assertRedirect();

        $response = $this->actingAs($this->poster)
            ->post(route('accounting.journal-entries.post', $draft));

        $response->assertRedirect(route('accounting.journal-entries.index'));
        $response->assertSessionHas('success');

        $draft->refresh();
        $this->assertSame(JournalEntry::STATUS_POSTED, $draft->status);
        $this->assertSame($this->poster->id, $draft->posted_by);
        $this->assertNotNull($draft->posted_at);
        $this->assertCount(2, $draft->lines);

        $this->assertDatabaseHas('account_audit_logs', [
            'journalable_type' => JournalEntry::class,
            'journalable_id' => $draft->id,
            'action' => 'posted',
        ]);
    }

    public function test_post_blocked_for_creator_due_to_sod(): void
    {
        $draft = $this->draftEntry();
        $this->post(route('accounting.journal-entries.finalize', $draft))->assertRedirect();

        // Creator is still signed in — SOD must reject the post attempt.
        $this->post(route('accounting.journal-entries.post', $draft))->assertForbidden();

        $this->assertSame(JournalEntry::STATUS_PENDING_APPROVAL, $draft->fresh()->status);
    }

    public function test_reopen_returns_finalized_to_draft_with_reason(): void
    {
        $draft = $this->draftEntry();
        $this->post(route('accounting.journal-entries.finalize', $draft))->assertRedirect();
        $this->assertSame(JournalEntry::STATUS_PENDING_APPROVAL, $draft->fresh()->status);

        $response = $this->post(route('accounting.journal-entries.reopen', $draft), [
            'reason' => 'Amounts need re-checking.',
        ]);

        $response->assertRedirect(route('accounting.journal-entries.index'));
        $this->assertSame(JournalEntry::STATUS_DRAFT, $draft->fresh()->status);

        $audit = AccountAuditLog::where('journalable_type', JournalEntry::class)
            ->where('journalable_id', $draft->id)
            ->where('action', 'reopened')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('Amounts need re-checking.', $audit->notes);
    }

    public function test_delete_removes_creator_draft(): void
    {
        $draft = $this->draftEntry();

        $response = $this->delete(route('accounting.journal-entries.destroy', $draft));

        $response->assertRedirect(route('accounting.journal-entries.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('journal_entries', ['id' => $draft->id]);
        $this->assertDatabaseMissing('journal_entry_lines', ['journal_entry_id' => $draft->id]);
    }

    public function test_delete_blocked_for_non_creator(): void
    {
        $draft = $this->draftEntry();

        $response = $this->actingAs($this->poster)
            ->delete(route('accounting.journal-entries.destroy', $draft));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('journal_entries', ['id' => $draft->id]);
    }

    public function test_delete_blocked_for_finalized_entry(): void
    {
        $draft = $this->draftEntry();
        $this->post(route('accounting.journal-entries.finalize', $draft))->assertRedirect();

        $response = $this->delete(route('accounting.journal-entries.destroy', $draft));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('journal_entries', ['id' => $draft->id]);
    }

    public function test_export_csv_streams_register(): void
    {
        $draft = $this->draftEntry();

        $response = $this->get(route('accounting.journal-entries.export'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Journal No', $csv);
        $this->assertStringContainsString($draft->journal_number, $csv);
    }

    public function test_creator_only_tooltip_renders_for_foreign_drafts(): void
    {
        $draft = $this->draftEntry();

        // Signed in as the poster (not the creator) — edit/delete must show as
        // creator-only, and the tooltip names the creator.
        $response = $this->actingAs($this->poster)
            ->get(route('accounting.journal-entries.index'));

        $response->assertOk();
        $response->assertSee('Only the creator', false);
        $response->assertSee($this->user->name);
    }
}

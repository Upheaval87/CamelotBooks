<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\Accounting\JournalReversalService;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class JournalReversalTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected User $reverser;

    protected Account $debitAccount;

    protected Account $creditAccount;

    protected CostCenter $costCenter;

    protected AccountingPeriod $openPeriod;

    protected AccountingPeriod $closedPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'Reversal Test Co',
            'company_code' => 'REVTEST',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        // Second user for controller-level actions: SOD forbids the actor who
        // created the record from reversing it.
        $this->reverser = User::factory()->create();
        $this->reverser->companies()->attach($this->company->id, ['role' => 'accountant']);
        $this->reverser->assignRole('accountant');

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

        $this->openPeriod = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026-08',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
        ]);

        $this->closedPeriod = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026-07',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'closed',
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    protected function postedEntry(): JournalEntry
    {
        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);

        return $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-08-10',
            'memo' => 'Original posted entry',
            'branch_id' => null,
            'is_adjusting_entry' => false,
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 750,
                    'credit' => 0,
                    'cost_center_id' => $this->costCenter->id,
                    'memo' => 'Dr leg',
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 750,
                ],
            ],
        ]);
    }

    protected function actingAsReverser(): void
    {
        setPermissionsTeamId($this->company->id);
        $this->actingAs($this->reverser);
    }

    public function test_immediate_reversal_creates_posted_mirror_and_marks_original_reversed(): void
    {
        $original = $this->postedEntry();

        $reversal = app(JournalReversalService::class)->createAndPost($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'reference' => 'REV-AUTO',
            'memo' => 'Duplicate posting — reversing to correct balance.',
        ]);

        $original->refresh();

        $this->assertEquals(JournalEntry::STATUS_REVERSED, $original->status);
        $this->assertEquals($reversal->id, $original->reversal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $reversal->status);
        $this->assertEquals('reversal', $reversal->source_module);
        $this->assertEquals($original->id, $reversal->linked_entry_id);
        $this->assertEquals('REV-AUTO', $reversal->reference);
        $this->assertEquals('2026-08-12', $reversal->date->format('Y-m-d'));

        $reversalLines = $reversal->lines->sortBy('id')->values();
        $originalLines = $original->lines->sortBy('id')->values();

        $this->assertEquals($originalLines[0]->account_id, $reversalLines[0]->account_id);
        $this->assertEquals($originalLines[0]->debit, $reversalLines[0]->credit);
        $this->assertEquals($originalLines[0]->credit, $reversalLines[0]->debit);

        $this->assertEquals($this->costCenter->id, (int) $reversalLines[0]->cost_center_id);
        $this->assertStringContainsString('Reversal of ' . $original->journal_number, (string) $reversalLines[0]->memo);
        $this->assertStringContainsString('Dr leg', (string) $reversalLines[0]->memo);

        $this->debitAccount->refresh();
        $this->creditAccount->refresh();
        $this->assertEquals(0.00, (float) $this->debitAccount->current_balance);
        $this->assertEquals(0.00, (float) $this->creditAccount->current_balance);
    }

    public function test_draft_reversal_does_not_mark_original_until_posted(): void
    {
        $original = $this->postedEntry();

        $draft = app(JournalReversalService::class)->createDraft($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'reference' => null,
            'memo' => 'Awaiting review.',
        ]);

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);
        $this->assertNull($original->reversal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_DRAFT, $draft->status);

        $posted = app(JournalReversalService::class)->postDraft($draft, $this->user->id);

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $original->status);
        $this->assertEquals($posted->id, $original->reversal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $posted->status);
    }

    public function test_discard_draft_keeps_original_posted_and_unlinked(): void
    {
        $original = $this->postedEntry();

        $draft = app(JournalReversalService::class)->createDraft($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'reference' => null,
            'memo' => 'To be discarded.',
        ]);

        app(JournalReversalService::class)->discardDraft($draft, $this->user->id);

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);
        $this->assertNull($original->reversal_entry_id);
        $this->assertDatabaseMissing('journal_entries', ['id' => $draft->id]);
        $this->assertDatabaseMissing('journal_entry_lines', ['journal_entry_id' => $draft->id]);
    }

    public function test_cannot_reverse_non_posted_entry(): void
    {
        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $draft = $engine->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-08-10',
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 10, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 10],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only posted journal entries can be reversed.');

        app(JournalReversalService::class)->createAndPost($draft, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'memo' => 'nope',
        ]);
    }

    public function test_second_reversal_is_blocked_while_one_is_open(): void
    {
        $original = $this->postedEntry();

        $draft = app(JournalReversalService::class)->createDraft($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'memo' => 'First draft.',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already has a reversal');

        app(JournalReversalService::class)->createAndPost($original, $this->user->id, [
            'reversal_date' => '2026-08-13',
            'memo' => 'Second attempt.',
        ]);

        // Ensure the draft was not consumed by the failed attempt.
        $this->assertEquals(JournalEntry::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_reversal_audit_records_reason_as_notes(): void
    {
        $original = $this->postedEntry();

        app(JournalReversalService::class)->createAndPost($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'memo' => 'Client requested correction.',
        ]);

        $audit = AccountAuditLog::where('journalable_type', JournalEntry::class)
            ->where('journalable_id', $original->id)
            ->where('action', 'reversed')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('Client requested correction.', $audit->notes);
        $this->assertEquals($this->user->id, $audit->user_id);
    }

    public function test_reversal_draft_created_audit_records_reason_and_draft_ref(): void
    {
        $original = $this->postedEntry();

        app(JournalReversalService::class)->createDraft($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'memo' => 'Hold for review.',
        ]);

        $audit = AccountAuditLog::where('journalable_type', JournalEntry::class)
            ->where('journalable_id', $original->id)
            ->where('action', 'reversal_draft_created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('Hold for review.', $audit->notes);
        $this->assertArrayHasKey('draft_entry_id', $audit->new_values);
    }

    public function test_period_closed_between_draft_and_post_blocks_posting(): void
    {
        $original = $this->postedEntry();

        $draft = app(JournalReversalService::class)->createDraft($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'memo' => 'Draft in open period.',
        ]);

        $this->openPeriod->update(['status' => 'closed']);

        try {
            app(JournalReversalService::class)->postDraft($draft, $this->user->id);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('closed', $e->getMessage());
        }

        // Original must remain posted and unlinked after the rejected post.
        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);
        $this->assertNull($original->reversal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_reversal_to_date_without_an_accounting_period_is_rejected(): void
    {
        $original = $this->postedEntry();

        // 2026-09 has no accounting period (open 2026-08, closed 2026-07) — the
        // fallback to the original's date must not silently shift the reversal.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No accounting period found for date 2026-09-05');

        app(JournalReversalService::class)->createAndPost($original, $this->user->id, [
            'reversal_date' => '2026-09-05',
            'memo' => 'No period exists for this date.',
        ]);

        // The original must remain posted and unlinked after the rejection.
        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);
        $this->assertNull($original->reversal_entry_id);
    }

    public function test_controller_reverse_endpoint_creates_draft(): void
    {
        $original = $this->postedEntry();

        $this->actingAsReverser();

        $this->post(
            route('accounting.journal-entries.reverse', $original),
            [
                'reversal_date' => '2026-08-12',
                'reference' => 'REV-CTRL',
                'memo' => 'Via controller.',
                'post_mode' => 'draft',
            ]
        )->assertRedirect(route('accounting.journal-entries.show', $original));

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);

        $draft = JournalEntry::where('company_id', $this->company->id)
            ->where('linked_entry_id', $original->id)
            ->first();
        $this->assertNotNull($draft);
        $this->assertEquals(JournalEntry::STATUS_DRAFT, $draft->status);
    }

    public function test_controller_reverse_endpoint_posts_immediately(): void
    {
        $original = $this->postedEntry();

        $this->actingAsReverser();

        $this->post(
            route('accounting.journal-entries.reverse', $original),
            [
                'reversal_date' => '2026-08-12',
                'reference' => 'REV-CTRL-POST',
                'memo' => 'Post now.',
                'post_mode' => 'immediate',
            ]
        )->assertRedirect(route('accounting.journal-entries.show', $original));

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $original->status);
        $this->assertNotNull($original->reversal_entry_id);
    }

    public function test_controller_post_reversal_posts_the_pending_draft(): void
    {
        $original = $this->postedEntry();

        app(JournalReversalService::class)->createDraft($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'memo' => 'Draft pending.',
        ]);

        $this->actingAsReverser();

        $this->post(route('accounting.journal-entries.post-reversal', $original))
            ->assertRedirect(route('accounting.journal-entries.show', $original));

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $original->status);
        $this->assertNotNull($original->reversal_entry_id);
    }

    public function test_controller_discard_reversal_removes_draft_and_keeps_original_posted(): void
    {
        $original = $this->postedEntry();

        app(JournalReversalService::class)->createDraft($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'memo' => 'Draft to drop.',
        ]);

        $this->actingAsReverser();

        $this->post(route('accounting.journal-entries.discard-reversal', $original))
            ->assertRedirect(route('accounting.journal-entries.show', $original));

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);
        $this->assertNull($original->reversal_entry_id);
        $this->assertDatabaseMissing(
            'journal_entries',
            ['company_id' => $this->company->id, 'source_module' => 'reversal', 'linked_entry_id' => $original->id]
        );
    }

    public function test_show_page_renders_reversal_banner_and_modal_for_posted_entry(): void
    {
        $original = $this->postedEntry();

        $this->get(route('accounting.journal-entries.show', $original))
            ->assertOk()
            ->assertSee('Reverse')
            ->assertSee('reversal-modal')
            ->assertSee('Create reversal')
            // The chooser, its explanatory copy and the two advisory notes were
            // removed: post_mode is now a fixed hidden "immediate", the reason is
            // a single-line input, and the derived date/reference are readonly.
            ->assertSee('name="post_mode" value="immediate"', false)
            ->assertSee('<input type="text" id="memo"', false)
            ->assertDontSee('Post immediately')
            ->assertDontSee('Save as draft')
            ->assertDontSee('Next reversal ref')
            ->assertDontSee('irreversible in effect');
    }

    public function test_reversal_modal_date_and_reference_are_readonly(): void
    {
        $original = $this->postedEntry();

        $this->get(route('accounting.journal-entries.show', $original))
            ->assertOk()
            ->assertSee('id="reversal_date" name="reversal_date" readonly', false)
            ->assertSee('id="reference" name="reference" readonly', false);
    }

    public function test_identity_confirm_required_when_threshold_met(): void
    {
        config(['journal_reversal.identity_verify_threshold' => 500]);

        $original = $this->postedEntry();

        $this->actingAsReverser();

        $this->post(
            route('accounting.journal-entries.reverse', $original),
            [
                'reversal_date' => '2026-08-12',
                'reference' => 'REV-NOCONF',
                'memo' => 'Should be gated.',
                'post_mode' => 'immediate',
            ]
        )->assertSessionHasErrors('identity_confirm');

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $original->status);
    }

    public function test_identity_confirm_not_required_below_threshold(): void
    {
        config(['journal_reversal.identity_verify_threshold' => 5000]);

        $original = $this->postedEntry();

        $this->actingAsReverser();

        $this->post(
            route('accounting.journal-entries.reverse', $original),
            [
                'reversal_date' => '2026-08-12',
                'reference' => 'REV-OK',
                'memo' => 'Below threshold.',
                'post_mode' => 'immediate',
            ]
        )->assertSessionHasNoErrors();

        $original->refresh();
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $original->status);
    }

    public function test_reverted_chain_reversal_uses_original_fields(): void
    {
        $original = $this->postedEntry();

        // Node 1: original reversed.
        $reversal1 = app(JournalReversalService::class)->createAndPost($original, $this->user->id, [
            'reversal_date' => '2026-08-12',
            'reference' => null,
            'memo' => 'First reversal.',
        ]);

        // Re-reverse the reversal — the new node must mirror reversal1's (already-mirrored) lines.
        $reversal2 = app(JournalReversalService::class)->createAndPost($reversal1, $this->user->id, [
            'reversal_date' => '2026-08-13',
            'reference' => null,
            'memo' => 'Second reversal (undo first).',
        ]);

        $reversal1->refresh();
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $reversal1->status);
        $this->assertEquals($reversal2->id, $reversal1->reversal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $reversal2->status);
        $this->assertEquals($reversal1->id, $reversal2->linked_entry_id);

        $r1Lines = $reversal1->lines->sortBy('id')->values();
        $r2Lines = $reversal2->lines->sortBy('id')->values();
        $this->assertEquals($r1Lines[0]->account_id, $r2Lines[0]->account_id);
        $this->assertEquals($r1Lines[0]->debit, $r2Lines[0]->credit);
        $this->assertEquals($r1Lines[0]->cost_center_id, $r2Lines[0]->cost_center_id);
    }
}
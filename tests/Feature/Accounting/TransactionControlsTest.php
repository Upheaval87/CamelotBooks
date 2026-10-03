<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\ReversalAuthorizationRequest;
use App\Models\TransactionReversalRequest;
use App\Models\User;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControlsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected User $accountant;

    protected Account $debitAccount;

    protected Account $creditAccount;

    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'Controls Test Co',
            'company_code' => 'CTRLTEST',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        // Second user: authorized accountant used for the maker/checker path.
        $this->accountant = User::factory()->create();
        $this->accountant->companies()->attach($this->company->id, ['role' => 'accountant']);
        $this->accountant->assignRole('accountant');

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

        app(NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    protected function postedEntry(): JournalEntry
    {
        return app(JournalPostingEngine::class)->post([
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

    protected function draftEntry(): JournalEntry
    {
        return app(JournalPostingEngine::class)->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-08-12',
            'memo' => 'Draft entry',
            'branch_id' => null,
            'is_adjusting_entry' => false,
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    protected function indexUrl(array $params = []): string
    {
        return route('accounting.transaction-controls.index', $params);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();

        $this->get($this->indexUrl())->assertRedirect(route('login'));
    }

    public function test_index_renders_workspace(): void
    {
        $this->get($this->indexUrl())
            ->assertOk()
            ->assertSee('Transaction Controls')
            ->assertSee('Transactions List')
            ->assertDontSee('Capture Reversal')
            ->assertSee('Unposted Transactions')
            ->assertSee('Authorization')
            ->assertSee('Reversals Processed')
            ->assertSee('You cannot approve a reversal you initiated')
            ->assertSee('transactionControls(', false);
    }

    public function test_date_gate_requires_both_dates(): void
    {
        $this->get($this->indexUrl(['tab' => 'reversal', 'from' => '2026-08-01']))
            ->assertOk()
            ->assertSee('Select both a From and a To date');
    }

    public function test_from_after_to_shows_error(): void
    {
        $this->get($this->indexUrl(['from' => '2026-08-10', 'to' => '2026-08-01']))
            ->assertOk()
            ->assertSee('must be on or before');
    }

    public function test_loaded_period_lists_posted_entries(): void
    {
        $entry = $this->postedEntry();

        $this->get($this->indexUrl(['from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()
            ->assertSee($entry->journal_number)
            ->assertSee('Posted')
            ->assertSee($this->user->name);
    }

    public function test_change_period_button_opens_period_modal(): void
    {
        $this->postedEntry();

        $this->get($this->indexUrl(['from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()
            ->assertSee('Change period')
            ->assertSee('open.period = true', false)
            ->assertSee('data-tc-period-form', false)
            ->assertSee('id="tc-period-title"', false)
            ->assertSee('Quick ranges')
            ->assertSee('Load transactions')
            ->assertSee("setRange(\$event, 'today')", false)
            ->assertSee("setRange(\$event, '7d')", false)
            ->assertSee("setRange(\$event, 'month')", false)
            ->assertSee("setRange(\$event, '30d')", false)
            // Footer exposes only a Close action (plus the header close icon).
            ->assertSee('Close</button>', false);
    }

    public function test_period_modal_button_absent_before_a_period_is_loaded(): void
    {
        $this->get($this->indexUrl())
            ->assertOk()
            ->assertDontSee('Change period')
            ->assertSee('Load posted & saved transactions');
    }

    public function test_reversal_pane_reads_actor_names_from_supplied_map_not_tenant_relations(): void
    {
        $entry = $this->postedEntry();
        $entry->setRelation('lines', collect());

        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            [$entry], 1, 15, 1, ['path' => $this->indexUrl()]
        );

        $html = \Illuminate\Support\Facades\Blade::render(
            "@include('accounting.transaction-controls._pane-reversal')",
            [
                'loaded' => true,
                'dateError' => null,
                'from' => '2026-08-01',
                'to' => '2026-08-31',
                'filters' => [],
                'typeOptions' => [],
                'awaitingAuthIds' => [],
                'transactions' => $transactions,
                'userNames' => [$this->user->id => 'Central Mapped Name'],
            ]
        );

        // The map value is rendered even though the createdBy relation (which,
        // in production, resolves on the tenant connection where "users" has no
        // name column) would otherwise supply a different value.
        $this->assertStringContainsString('Central Mapped Name', $html);
        $this->assertStringNotContainsString($this->user->name, $html);
    }

    public function test_capture_creates_pending_authorization_and_leaves_entry_posted(): void
    {
        $entry = $this->postedEntry();

        $this->post(route('accounting.transaction-controls.reverse', ['id' => $entry->id]), [
            'reversal_date' => '2026-08-15',
            'reason' => 'Duplicate posting identified',
            'reference' => 'REV-1',
        ])->assertRedirect();

        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->fresh()->status);
        $this->assertDatabaseHas('transaction_reversal_requests', [
            'company_id' => $this->company->id,
            'journal_entry_id' => $entry->id,
            'status' => TransactionReversalRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('journal_entries', [
            'company_id' => $this->company->id,
            'source_module' => 'reversal',
        ]);
    }

    public function test_reverse_authorization_creates_pending_request(): void
    {
        $entry = $this->postedEntry();

        $this->post(route('accounting.transaction-controls.reverse', ['id' => $entry->id]), [
            'reversal_date' => '2026-08-15',
            'reason' => 'Awaiting controller approval',
        ])->assertRedirect();

        $this->assertDatabaseHas('transaction_reversal_requests', [
            'company_id' => $this->company->id,
            'journal_entry_id' => $entry->id,
            'status' => TransactionReversalRequest::STATUS_PENDING,
        ]);
    }

    public function test_capture_ignores_legacy_mode_and_always_requires_authorization(): void
    {
        $entry = $this->postedEntry();

        $this->post(route('accounting.transaction-controls.reverse', ['id' => $entry->id]), [
            'mode' => 'immediate',
            'reversal_date' => '2026-08-15',
            'reason' => 'Legacy mode must not bypass authorization',
        ])->assertRedirect();

        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->fresh()->status);
        $this->assertDatabaseHas('transaction_reversal_requests', [
            'company_id' => $this->company->id,
            'journal_entry_id' => $entry->id,
            'status' => TransactionReversalRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('journal_entries', [
            'company_id' => $this->company->id,
            'source_module' => 'reversal',
        ]);
    }

    public function test_duplicate_capture_blocked_while_awaiting_authorization(): void
    {
        $entry = $this->postedEntry();

        app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'First capture request',
                'reversal_method' => 'full',
            ]);

        $this->post(route('accounting.transaction-controls.reverse', ['id' => $entry->id]), [
            'reversal_date' => '2026-08-16',
            'reason' => 'Second capture must be blocked',
        ])->assertRedirect();

        $this->assertSame(1, TransactionReversalRequest::where('journal_entry_id', $entry->id)->count());
    }

    public function test_reverse_requires_a_reason(): void
    {
        $entry = $this->postedEntry();

        $this->post(route('accounting.transaction-controls.reverse', ['id' => $entry->id]), [
            'reversal_date' => '2026-08-15',
            'reason' => 'no',
        ])->assertSessionHasErrors('reason');

        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->fresh()->status);
    }

    public function test_unposted_pane_lists_draft_entries(): void
    {
        $draft = $this->draftEntry();

        $this->get($this->indexUrl(['tab' => 'unposted']))
            ->assertOk()
            ->assertSee($draft->journal_number)
            ->assertSee('Draft');
    }

    public function test_unposted_pane_uses_unposted_label_and_shows_post_action(): void
    {
        $draft = $this->draftEntry();
        app(JournalPostingEngine::class)->finalize($draft->id, $this->user->id);

        $this->get($this->indexUrl(['tab' => 'unposted']))
            ->assertOk()
            ->assertSee($draft->journal_number)
            ->assertSee('Unposted — awaiting posting')
            ->assertSee('Unposted')
            ->assertDontSee('Finalized')
            ->assertSee('askPost(' . $draft->id . ')', false)
            ->assertSee('class="ib okb"', false);
    }

    public function test_unposted_pane_hides_finalized_hint_text(): void
    {
        $this->get($this->indexUrl(['tab' => 'unposted']))
            ->assertOk()
            ->assertDontSee('Draft and finalized journals awaiting posting')
            ->assertSee('Draft and unposted journals will appear here.');
    }

    public function test_reversals_processed_pane_hides_intro_hint_text(): void
    {
        $this->get($this->indexUrl(['tab' => 'reversals_processed']))
            ->assertOk()
            ->assertDontSee('Reversals authorized in the Authorization tab and posted to the ledger')
            ->assertSee('No reversals processed yet');
    }

    public function test_post_action_moves_unposted_entry_to_ledger(): void
    {
        $draft = $this->draftEntry();
        app(JournalPostingEngine::class)->finalize($draft->id, $this->user->id);

        $this->assertSame(JournalEntry::STATUS_PENDING_APPROVAL, $draft->fresh()->status);

        $this->post(route('accounting.transaction-controls.post', ['id' => $draft->id]))
            ->assertRedirect();

        $this->assertSame(JournalEntry::STATUS_POSTED, $draft->fresh()->status);
    }

    public function test_authorization_pane_lists_assigned_queue(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Please authorize this reversal',
                'reversal_method' => 'full',
            ]);

        $this->actingAs($this->accountant)
            ->get($this->indexUrl(['tab' => 'authorization']))
            ->assertOk()
            ->assertSee($request->reference_number)
            ->assertSee($this->user->name);
    }

    public function test_approve_posts_reversal(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Approve and post this reversal',
                'reversal_method' => 'full',
            ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $this->accountant->id)
            ->firstOrFail();

        $this->actingAs($this->accountant)
            ->post(route('accounting.transaction-controls.approve', ['id' => $auth->id]), [
                'comments' => 'Approved',
            ])
            ->assertRedirect();

        $this->assertSame(TransactionReversalRequest::STATUS_REVERSED, $request->fresh()->status);
        $this->assertSame(JournalEntry::STATUS_REVERSED, $entry->fresh()->status);
    }

    public function test_reject_records_reason(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Request that will be declined',
                'reversal_method' => 'full',
            ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $this->accountant->id)
            ->firstOrFail();

        $this->actingAs($this->accountant)
            ->post(route('accounting.transaction-controls.reject', ['id' => $auth->id]), [
                'reason' => 'Not a valid business reason',
            ])
            ->assertRedirect();

        $fresh = $request->fresh();
        $this->assertSame(TransactionReversalRequest::STATUS_REJECTED, $fresh->status);
        $this->assertSame('Not a valid business reason', $fresh->rejection_reason);
        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->fresh()->status);
    }

    public function test_rejected_request_clears_pending_chain_and_allows_recapture(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Will be rejected then re-captured',
                'reversal_method' => 'full',
            ]);

        // Simulate a multi-level chain with an extra pending authorizer.
        ReversalAuthorizationRequest::create([
            'company_id' => $this->company->id,
            'reversal_request_id' => $request->id,
            'approval_level' => 2,
            'assigned_to' => $this->user->id,
            'status' => 'pending',
        ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $this->accountant->id)
            ->firstOrFail();

        $this->actingAs($this->accountant)
            ->post(route('accounting.transaction-controls.reject', ['id' => $auth->id]), [
                'reason' => 'Rejected for retest',
            ])
            ->assertRedirect();

        $this->assertSame(0, ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('status', 'pending')->count());
        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->fresh()->status);

        // The entry is capturable again after rejection.
        $this->actingAs($this->user)
            ->post(route('accounting.transaction-controls.reverse', ['id' => $entry->id]), [
                'reversal_date' => '2026-08-16',
                'reason' => 'Second attempt after rejection',
            ])
            ->assertRedirect();

        $this->assertSame(2, TransactionReversalRequest::where('journal_entry_id', $entry->id)->count());
    }

    public function test_awaiting_authorization_entry_is_flagged_in_capture_list(): void
    {
        $entry = $this->postedEntry();

        app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Awaiting authorization flag test',
                'reversal_method' => 'full',
            ]);

        $this->get($this->indexUrl(['from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()
            ->assertSee('Awaiting authorization');
    }

    public function test_requester_cannot_authorize_own_request(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Maker cannot approve this',
                'reversal_method' => 'full',
            ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $this->user->id)
            ->first();

        if (! $auth) {
            $auth = ReversalAuthorizationRequest::create([
                'company_id' => $this->company->id,
                'reversal_request_id' => $request->id,
                'approval_level' => 99,
                'assigned_to' => $this->user->id,
                'status' => 'pending',
            ]);
        }

        $this->post(route('accounting.transaction-controls.approve', ['id' => $auth->id]))
            ->assertForbidden();
    }

    public function test_authorization_queue_is_populated_without_accountant_role(): void
    {
        // Simulates the live tenant: no user holds the accountant role. The old
        // default chain created ZERO rows here, so the request never reached the
        // Authorization tab. It must now always be authorizable.
        setPermissionsTeamId($this->company->id);
        $this->accountant->syncRoles([]);

        $reviewer = User::factory()->create();
        $reviewer->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $reviewer->assignRole('company_admin');

        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'No accountant exists for this tenant',
                'reversal_method' => 'full',
            ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('status', 'pending')
            ->first();

        $this->assertNotNull($auth, 'An authorization row must always be created.');
        $this->assertNotSame(
            $this->user->id,
            $auth->assigned_to,
            'The requester must never be the assigned approver.'
        );

        // A different permitted reviewer sees it in the Authorization tab...
        $this->actingAs($reviewer)
            ->get($this->indexUrl(['tab' => 'authorization']))
            ->assertOk()
            ->assertSee($request->reference_number);

        // ...and can authorize it even though the row was not assigned to them.
        $this->actingAs($reviewer)
            ->post(route('accounting.transaction-controls.approve', ['id' => $auth->id]), [
                'comments' => 'Approved by another reviewer',
            ])
            ->assertRedirect();

        $this->assertSame(TransactionReversalRequest::STATUS_REVERSED, $request->fresh()->status);
        $this->assertSame(JournalEntry::STATUS_REVERSED, $entry->fresh()->status);
    }

    public function test_requester_sees_separation_of_duties_notice(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Requester cannot decide their own request',
                'reversal_method' => 'full',
            ]);

        $this->actingAs($this->user)
            ->get($this->indexUrl(['tab' => 'authorization']))
            ->assertOk()
            ->assertSee($request->reference_number)
            ->assertSee('Separation of duties');
    }

    public function test_requester_payload_marks_own_request(): void
    {
        $entry = $this->postedEntry();
        app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Requester marks own request',
                'reversal_method' => 'full',
            ]);

        $this->actingAs($this->user)
            ->get($this->indexUrl(['tab' => 'authorization']))
            ->assertOk()
            ->assertSee('\\u0022isRequester\\u0022:true');
    }

    public function test_orphaned_pending_request_is_backfilled_on_index(): void
    {
        // Simulates the live tenant: a request captured before the fix has zero
        // authorization rows, so it never appeared in the Authorization tab.
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Legacy request with no authorization rows',
                'reversal_method' => 'full',
            ]);

        ReversalAuthorizationRequest::where('reversal_request_id', $request->id)->delete();
        $this->assertDatabaseCount('reversal_authorization_requests', 0);

        $this->actingAs($this->accountant)
            ->get($this->indexUrl(['tab' => 'authorization']))
            ->assertOk()
            ->assertSee($request->reference_number);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('status', 'pending')
            ->first();
        $this->assertNotNull($auth, 'The backfill must create a chain.');
        $this->assertNotSame($this->user->id, $auth->assigned_to);

        $this->actingAs($this->accountant)
            ->post(route('accounting.transaction-controls.approve', ['id' => $auth->id]))
            ->assertRedirect();

        $this->assertSame(TransactionReversalRequest::STATUS_REVERSED, $request->fresh()->status);
    }

    public function test_reopen_returns_finalized_entry_to_draft(): void
    {
        $draft = $this->draftEntry();
        app(JournalPostingEngine::class)->finalize($draft->id, $this->user->id);

        $this->post(route('accounting.transaction-controls.reopen', ['id' => $draft->id]), [
            'reason' => 'Correction needed',
        ])->assertRedirect();

        $this->assertSame(JournalEntry::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_destroy_deletes_a_draft(): void
    {
        $draft = $this->draftEntry();

        $this->delete(route('accounting.transaction-controls.destroy', ['id' => $draft->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('journal_entries', ['id' => $draft->id]);
    }

    public function test_reversals_processed_tab_lists_executed_reversals(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Processed reversal for the register',
                'reversal_method' => 'full',
            ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $this->accountant->id)
            ->firstOrFail();

        $this->actingAs($this->accountant)
            ->post(route('accounting.transaction-controls.approve', ['id' => $auth->id]))
            ->assertRedirect();

        $reversalNumber = $request->fresh()->reversal->reversal_number;

        $this->actingAs($this->user)
            ->get($this->indexUrl(['tab' => 'reversals_processed']))
            ->assertOk()
            ->assertSee('Reversals Processed')
            ->assertSee($reversalNumber)
            ->assertSee($entry->journal_number)
            ->assertSee($this->accountant->name);
    }

    public function test_reversals_processed_tab_is_empty_before_any_reversal(): void
    {
        $this->get($this->indexUrl(['tab' => 'reversals_processed']))
            ->assertOk()
            ->assertSee('No reversals processed yet');
    }

    public function test_capture_reversal_hides_reversal_entries(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Reversal entry must not be a capture candidate',
                'reversal_method' => 'full',
            ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $this->accountant->id)
            ->firstOrFail();

        $this->actingAs($this->accountant)
            ->post(route('accounting.transaction-controls.approve', ['id' => $auth->id]))
            ->assertRedirect();

        $reversalEntry = JournalEntry::forCompany($this->company->id)
            ->where('source_module', 'reversal')
            ->firstOrFail();

        $this->actingAs($this->user)
            ->get($this->indexUrl(['tab' => 'reversal', 'from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()
            ->assertSee($entry->journal_number)
            ->assertDontSee($reversalEntry->journal_number);
    }

    public function test_capture_type_filter_excludes_reversal_type(): void
    {
        $entry = $this->postedEntry();
        $request = app(\App\Services\Accounting\TransactionReversalService::class)
            ->requestReversal($this->company->id, $entry->id, $this->user->id, [
                'reversal_date' => '2026-08-15',
                'reason' => 'Populate a reversal source module',
                'reversal_method' => 'full',
            ]);

        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $this->accountant->id)
            ->firstOrFail();

        $this->actingAs($this->accountant)
            ->post(route('accounting.transaction-controls.approve', ['id' => $auth->id]))
            ->assertRedirect();

        $this->actingAs($this->user)
            ->get($this->indexUrl(['from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()
            ->assertDontSee('<option value="reversal"', false);
    }
}

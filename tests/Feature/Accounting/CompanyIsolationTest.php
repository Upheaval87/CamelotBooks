<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Company $companyB;

    protected Account $companyAAccount;

    protected AccountingPeriod $periodA;

    protected AccountingPeriod $periodB;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();

        $this->companyA = Company::create([
            'name' => 'Company A',
            'company_code' => 'COA',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B',
            'company_code' => 'COB',
            'is_active' => true,
        ]);

        $this->user->companies()->attach($this->companyA->id, ['role' => 'company_admin']);
        $this->user->companies()->attach($this->companyB->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->companyA->id);
        $this->user->assignRole('company_admin');
        setPermissionsTeamId($this->companyB->id);
        $this->user->assignRole('company_admin');
        setPermissionsTeamId($this->companyA->id);
        $this->user->update(['current_company_id' => $this->companyA->id]);

        $this->companyAAccount = Account::create([
            'company_id' => $this->companyA->id,
            'code' => '1000',
            'name' => 'Company A Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->periodA = AccountingPeriod::create([
            'company_id' => $this->companyA->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->periodB = AccountingPeriod::create([
            'company_id' => $this->companyB->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->companyA->id);
        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->companyB->id);
    }

    public function test_user_cannot_see_other_company_accounts_via_controller(): void
    {
        $companyBAccount = Account::create([
            'company_id' => $this->companyB->id,
            'code' => '1000',
            'name' => 'Company B Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->companyA->id])
            ->get(route('accounting.accounts.index'))
            ->assertOk();

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->companyA->id])
            ->get(route('accounting.accounts.index'));

        $response->assertDontSee($companyBAccount->name);
    }

    public function test_journal_entries_from_company_a_not_visible_when_company_b_is_active(): void
    {
        $engine = app(JournalPostingEngine::class);

        $creditAccountA = Account::create([
            'company_id' => $this->companyA->id,
            'code' => '4000',
            'name' => 'Company A Revenue',
            'type' => 'income',
            'sub_type' => 'operating_income',
            'is_active' => true,
        ]);

        $engine->post([
            'company_id' => $this->companyA->id,
            'created_by' => $this->user->id,
            'date' => '2026-02-15',
            'lines' => [
                [
                    'account_id' => $this->companyAAccount->id,
                    'debit' => 500,
                    'credit' => 0,
                ],
                [
                    'account_id' => $creditAccountA->id,
                    'debit' => 0,
                    'credit' => 500,
                ],
            ],
        ]);

        $responseA = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->companyA->id])
            ->get(route('accounting.journal-entries.index'));

        $responseA->assertOk();
        $responseA->assertSee('JE-2026');

        $responseB = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->companyB->id])
            ->get(route('accounting.journal-entries.index'));

        $responseB->assertOk();
        $responseB->assertDontSee('JE-2026');
    }

    public function test_posting_against_other_company_accounts_from_company_context_fails(): void
    {
        $creditAccountB = Account::create([
            'company_id' => $this->companyB->id,
            'code' => '4000',
            'name' => 'Company B Revenue',
            'type' => 'income',
            'sub_type' => 'operating_income',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->companyA->id])
            ->post(route('accounting.journal-entries.store'), [
                'date' => '2026-02-15',
                'action' => 'post',
                'lines' => [
                    [
                        'account_id' => $this->companyAAccount->id,
                        'debit' => 100,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $creditAccountB->id,
                        'debit' => 0,
                        'credit' => 100,
                    ],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_company_b_accounts_are_not_selectable_in_company_a_context(): void
    {
        Account::create([
            'company_id' => $this->companyB->id,
            'code' => '1000',
            'name' => 'Company B Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->companyA->id])
            ->get(route('accounting.journal-entries.create'));

        $response->assertOk();
        $response->assertDontSee('Company B Cash');
    }

    public function test_each_company_has_independent_account_balances(): void
    {
        $engine = app(JournalPostingEngine::class);

        $creditA = Account::create([
            'company_id' => $this->companyA->id,
            'code' => '4000',
            'name' => 'Company A Revenue',
            'type' => 'income',
            'sub_type' => 'operating_income',
            'is_active' => true,
        ]);

        $engine->post([
            'company_id' => $this->companyA->id,
            'created_by' => $this->user->id,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->companyAAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $creditA->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $this->companyAAccount->refresh();
        $creditA->refresh();

        $this->assertEquals(1000.00, (float) $this->companyAAccount->current_balance);
        $this->assertEquals(1000.00, (float) $creditA->current_balance);

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $this->companyA->id,
            'status' => 'posted',
        ]);
        $this->assertEquals(1, JournalEntry::where('company_id', $this->companyA->id)->count());
        $this->assertEquals(0, JournalEntry::where('company_id', $this->companyB->id)->count());
    }
}

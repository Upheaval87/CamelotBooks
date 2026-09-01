<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\MethodConversion;
use App\Models\User;
use App\Services\Admin\NumberingSequenceService;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4 — Switch to Accrual (spec §5). A gated, journaled, one-way conversion
 * for cash-basis companies.
 *
 * Covers: the GET/POST route gate (admin AND cash-method only), the cut-off
 * validation, draft persistence + audit, and the atomic activation (conversion
 * journal via JournalPostingEngine, AR/AP/inventory activation, period basis
 * flags, company flag flip, feature enable, audit trail).
 */
class SwitchToAccrualTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $admin;
    protected AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'SWACC',
            'name' => 'Switch Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'accounting_method' => Company::METHOD_CASH,
        ]);
        $this->admin->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->admin->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => 'August 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
        ]);

        app(NumberingSequenceService::class)->seedDefaults($this->company->id);

        $this->seedConversionAccounts();
    }

    protected function seedConversionAccounts(): void
    {
        $accounts = [
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'receivable'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1300', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'payable'],
            ['code' => '2100', 'name' => 'Accrued Expenses', 'type' => 'liability', 'sub_type' => 'accrual'],
            ['code' => '2200', 'name' => 'Unearned Revenue', 'type' => 'liability', 'sub_type' => 'deferred'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'sub_type' => 'retained_earnings'],
        ];
        foreach ($accounts as $account) {
            Account::create([
                'company_id' => $this->company->id,
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'sub_type' => $account['sub_type'],
                'is_active' => false,
            ]);
        }
    }

    public function test_page_renders_for_admin_on_cash_company(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.switch_accrual'))
            ->assertOk()
            ->assertSee('Switch to Accrual — controlled conversion', false)
            ->assertSee('Cut-off date', false)
            ->assertSee('Capture opening balances at cut-off', false)
            ->assertSee('Conversion journal — auto-balanced to opening equity', false)
            ->assertSee('Activate conversion', false)
            ->assertSee('Accounts Payable (incurred, not paid)', false)
            ->assertSee('Retained Earnings — opening adjustment', false);
    }

    public function test_page_blocks_non_admin_with_403(): void
    {
        $accountant = User::factory()->create();
        $accountant->companies()->attach($this->company->id, ['role' => 'accountant']);
        setPermissionsTeamId($this->company->id);
        $accountant->assignRole('accountant');
        session(['current_company_id' => $this->company->id]);

        $this->actingAs($accountant)
            ->get(route('settings.switch_accrual'))
            ->assertForbidden();
    }

    public function test_page_blocks_admin_on_already_accrual_company(): void
    {
        $this->company->update(['accounting_method' => Company::METHOD_ACCRUAL]);

        $this->actingAs($this->admin)
            ->get(route('settings.switch_accrual'))
            ->assertForbidden();
    }

    public function test_draft_persists_conversion_and_audits_opening_balances(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.switch_accrual.store'), [
                'cut_off_date' => '2026-08-31',
                'treatment' => 'prospective',
                'ar' => 500,
                'ap' => 200,
                'action' => 'draft',
            ])
            ->assertRedirect(route('settings.switch_accrual'))
            ->assertSessionHas('status');

        $conversion = MethodConversion::query()
            ->forCompany($this->company->id)
            ->first();

        $this->assertNotNull($conversion);
        $this->assertSame(MethodConversion::STATUS_DRAFT, $conversion->status);
        $this->assertSame('2026-08-31', $conversion->cut_off_date->format('Y-m-d'));
        $this->assertSame('prospective', $conversion->treatment);

        $audited = AuditLog::query()
            ->where('company_id', $this->company->id)
            ->where('action', 'conversion_opening_balances')
            ->exists();
        $this->assertTrue($audited);

        $this->company->refresh();
        $this->assertTrue($this->company->isCashBasis());
    }

    public function test_activate_posts_conversion_journal_and_flips_method(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.switch_accrual.store'), [
                'cut_off_date' => '2026-08-31',
                'treatment' => 'prospective',
                'ar' => 500,
                'inv' => 300,
                'pre' => 50,
                'ap' => 200,
                'acc' => 100,
                'une' => 60,
                'action' => 'activate',
            ])
            ->assertRedirect(route('settings.switch_accrual'));

        $this->company->refresh();
        $this->assertTrue($this->company->isAccrual());

        $conversion = MethodConversion::query()
            ->forCompany($this->company->id)
            ->latest()
            ->first();
        $this->assertNotNull($conversion);
        $this->assertTrue($conversion->isActivated());
        $this->assertNotNull($conversion->conversion_journal_id);

        $entry = JournalEntry::find($conversion->conversion_journal_id);
        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->status);
        $this->assertSame('method_conversion', $entry->source_module);

        $entry->load('lines.account');
        $debit = $entry->lines->sum('debit');
        $credit = $entry->lines->sum('credit');
        $this->assertSame(850.0, $debit);
        $this->assertSame(850.0, $credit);

        // Dr AR(500)+Inv(300)+Pre(50) = 850; Cr AP(200)+Acc(100)+Une(60)=360;
        // plug to Retained Earnings (credit) = 490.
        $re = Account::forCompany($this->company->id)->where('code', '3100')->first();
        $reLine = $entry->lines->firstWhere('account_id', $re->id);
        $this->assertNotNull($reLine);
        $this->assertSame(490.0, (float) $reLine->credit);
    }

    public function test_activate_activates_ar_ap_inventory_accounts_and_inventory_feature(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.switch_accrual.store'), [
                'cut_off_date' => '2026-08-31',
                'treatment' => 'prospective',
                'ar' => 100,
                'ap' => 40,
                'action' => 'activate',
            ])
            ->assertRedirect(route('settings.switch_accrual'));

        foreach (['1100', '1200', '1300', '2000', '2100', '2200'] as $code) {
            $this->assertTrue(
                Account::forCompany($this->company->id)->where('code', $code)->first()->is_active,
                "Account $code should be active after activation."
            );
        }

        $this->assertTrue(FeatureManagement::isEnabled($this->company->id, 'inventory'), 'inventory feature should be enabled.');
    }

    public function test_activate_flags_period_bases_around_cut_off(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.switch_accrual.store'), [
                'cut_off_date' => '2026-08-31',
                'treatment' => 'prospective',
                'ar' => 100,
                'ap' => 40,
                'action' => 'activate',
            ])
            ->assertRedirect(route('settings.switch_accrual'));

        $this->assertSame(
            'cash',
            AccountingPeriod::forCompany($this->company->id)->where('label', 'July 2026')->first()->basis
        );
        $this->assertSame(
            'accrual',
            AccountingPeriod::forCompany($this->company->id)->where('label', 'August 2026')->first()->basis
        );
    }

    public function test_activate_writes_audit_log_entry(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.switch_accrual.store'), [
                'cut_off_date' => '2026-08-31',
                'treatment' => 'prospective',
                'ar' => 100,
                'ap' => 40,
                'action' => 'activate',
            ])
            ->assertRedirect(route('settings.switch_accrual'));

        $this->assertTrue(
            AuditLog::query()
                ->where('company_id', $this->company->id)
                ->where('action', 'accounting_method_switched')
                ->exists()
        );
    }

    public function test_cut_off_before_last_posted_period_is_rejected(): void
    {
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'closed',
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.switch_accrual.store'), [
                'cut_off_date' => '2026-07-01',
                'treatment' => 'prospective',
                'ar' => 100,
                'action' => 'activate',
            ])
            ->assertSessionHasErrors('cut_off_date');

        $this->company->refresh();
        $this->assertTrue($this->company->isCashBasis());
        $this->assertCount(0, MethodConversion::query()->forCompany($this->company->id)->get());
    }

    public function test_activate_requires_at_least_one_opening_balance(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.switch_accrual.store'), [
                'cut_off_date' => '2026-08-31',
                'treatment' => 'prospective',
                'ar' => 0,
                'ap' => 0,
                'inv' => 0,
                'pre' => 0,
                'acc' => 0,
                'une' => 0,
                'action' => 'activate',
            ])
            ->assertSessionHasErrors('cut_off_date');

        $this->company->refresh();
        $this->assertTrue($this->company->isCashBasis());
    }
}

<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCenterTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        foreach (['banking', 'fixed_assets', 'inventory', 'payroll', 'pos', 'purchasing'] as $feature) {
            FeatureManagement::enable($this->company->id, $feature);
        }

        Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    public function test_index_renders_variant_a_markup(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.report-center.index'))
            ->assertOk()
            ->assertSee('Report Center', false)
            ->assertSee('va-suite', false)
            ->assertSee('reportCenter(', false)
            ->assertSee('All Reports', false)
            ->assertSee('Trial Balance', false)
            ->assertSee('Favourites', false)
            ->assertSee('Name A–Z', false)
            ->assertSee('Name Z–A', false)
            ->assertSee('Clear search &amp; filters', false)
            ->assertSee('va-nav-ic', false)
            ->assertSee('va-tile', false)
            ->assertSee('va-fmt', false)
            ->assertSee('va-chev', false);
    }

    public function test_favorite_toggle_persists_to_user_preferences(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('accounting.report-center.toggle-favorite', ['key' => 'trial_balance']))
            ->assertOk()
            ->assertJson(['favorited' => true]);

        $prefs = UserPreference::where('user_id', $this->user->id)->first();
        $this->assertNotNull($prefs);
        $this->assertContains('trial_balance', $prefs->report_favourites);
    }

    public function test_favorite_toggle_removes_key(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('accounting.report-center.toggle-favorite', ['key' => 'trial_balance']))
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('accounting.report-center.toggle-favorite', ['key' => 'trial_balance']))
            ->assertOk()
            ->assertJson(['favorited' => false]);

        $prefs = UserPreference::where('user_id', $this->user->id)->first();
        $this->assertNotNull($prefs);
        $this->assertNotContains('trial_balance', $prefs->report_favourites);
    }

    public function test_favorite_toggle_invalid_key_returns_404(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('accounting.report-center.toggle-favorite', ['key' => 'not_a_real_report']))
            ->assertNotFound();
    }

    public function test_favourites_shelf_renders_for_favourited_report(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('accounting.report-center.toggle-favorite', ['key' => 'trial_balance']))
            ->assertOk();

        $this->actingAs($this->user)
            ->get(route('accounting.report-center.index'))
            ->assertOk()
            ->assertSee('va-shelf', false)
            ->assertSee('favorites: JSON.parse(', false)
            ->assertSee('\u0022name\u0022:\u0022Trial Balance\u0022,\u0022url\u0022', false);
    }

    public function test_inaccessible_feature_report_dropped_from_favourites_shelf(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('accounting.report-center.toggle-favorite', ['key' => 'pos_sales_by_cashier']))
            ->assertOk();

        FeatureManagement::disable($this->company->id, 'pos');

        $this->actingAs($this->user)
            ->get(route('accounting.report-center.index'))
            ->assertOk()
            ->assertSee('favorites: [],', false);
    }

    public function test_groups_data_contains_urls_and_favourite_flags(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.report-center.index'))
            ->assertOk()
            ->assertSee('\\u0022trial_balance\\u0022', false)
            ->assertSee('\\u0022is_favorite\\u0022', false)
            ->assertSee('\\u0022icon\\u0022', false)
            ->assertSee('\\u0022formats\\u0022', false)
            ->assertSee('\\u0022PDF\\u0022', false)
            ->assertSee('\\u0022CSV\\u0022', false);
    }

    public function test_index_renders_full_registry(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('accounting.report-center.index'))
            ->assertOk();

        $content = $response->getContent();

        $this->assertSame(74, substr_count($content, '\\u0022description\\u0022'));

        foreach ([
            'Financial Statements',
            'Sales / Accounts Receivable',
            'Purchasing / Accounts Payable',
            'Inventory',
            'Banking',
            'Fixed Assets',
            'Payroll',
            'Compliance & Audit',
            'Analytics',
            'Business Intelligence',
            'Point of Sale',
        ] as $label) {
            $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
            $encoded = substr(json_encode(json_encode($label, $flags), $flags), 1, -1);
            $this->assertStringContainsString('\\u0022label\\u0022:' . $encoded, $content);
        }

        foreach ([
            'financial_ratios',
            'true_total_cost',
            'pos_x_report',
            'purchase_register',
            'cheque_register',
            'payslip_report',
            'asset_disposal_report',
        ] as $key) {
            $this->assertStringContainsString('\\u0022key\\u0022:\\u0022' . $key . '\\u0022', $content);
        }

        $this->assertStringContainsString('\\u0022url\\u0022', $content);
        $this->assertStringNotContainsString('\\u0022url\\u0022:null', $content);
    }
}

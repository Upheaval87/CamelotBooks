<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;
use Tests\TestCase;

class FixedAssetsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'FASTAT',
            'name' => 'FA Stats Test',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        CompanyModule::updateOrCreate(
            ['company_id' => $this->company->id, 'module_id' => Module::where('code', 'fixed_assets')->first()->id],
            ['is_active' => true]
        );

        // Seed GL accounts required by fa_assets FK constraints.
        Account::create(['company_id' => $this->company->id, 'code' => '1700', 'name' => 'Fixed Assets GL', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '1800', 'name' => 'Accum Dep GL', 'type' => 'contra_asset', 'sub_type' => 'accumulated_depreciation', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '6500', 'name' => 'Dep Expense GL', 'type' => 'expense', 'sub_type' => 'depreciation', 'is_active' => true]);
    }

    public function test_dashboard_renders_when_feature_enabled(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('accounting.fixed-assets.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Fixed Assets Centre');
        $response->assertSee('Total Assets');
        $response->assertSee('Total Cost');
        $response->assertSee('Net Book Value');
    }

    public function test_dashboard_returns_404_when_feature_disabled(): void
    {
        CompanyModule::where('company_id', $this->company->id)
            ->where('module_id', Module::where('code', 'fixed_assets')->first()->id)
            ->update(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('accounting.fixed-assets.dashboard'));

        $response->assertNotFound();
    }

    public function test_dashboard_displays_kpi_counts(): void
    {
        $assetAccountId = DB::table('accounts')
            ->where('company_id', $this->company->id)->where('code', '1700')->first()->id;
        $accumAccountId = DB::table('accounts')
            ->where('company_id', $this->company->id)->where('code', '1800')->first()->id;
        $depExpAccountId = DB::table('accounts')
            ->where('company_id', $this->company->id)->where('code', '6500')->first()->id;

        $category = DB::table('fa_categories')->insertGetId([
            'company_id' => $this->company->id,
            'code' => 'MACH',
            'name' => 'Machinery',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fa_assets')->insert([
            [
                'company_id' => $this->company->id,
                'category_id' => $category,
                'asset_code' => 'A-001',
                'name' => 'Laptop',
                'acquisition_date' => '2026-01-01',
                'acquisition_cost' => 1500,
                'net_book_value' => 1200,
                'accumulated_depreciation' => 300,
                'useful_life' => 36,
                'asset_account_id' => $assetAccountId,
                'accum_dep_account_id' => $accumAccountId,
                'dep_expense_account_id' => $depExpAccountId,
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $this->company->id,
                'category_id' => $category,
                'asset_code' => 'A-002',
                'name' => 'Desk',
                'acquisition_date' => '2026-02-01',
                'acquisition_cost' => 800,
                'accumulated_depreciation' => 0,
                'net_book_value' => 800,
                'useful_life' => 60,
                'asset_account_id' => $assetAccountId,
                'accum_dep_account_id' => $accumAccountId,
                'dep_expense_account_id' => $depExpAccountId,
                'is_active' => true,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accounting.fixed-assets.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('2');
        $response->assertSee('Laptop');
        $response->assertSee('Desk');
        $response->assertSee('A-001');
        $response->assertSee('A-002');
        $response->assertSee('Machinery');
    }

    public function test_nav_routes_no_longer_redirect_to_dashboard(): void
    {
        $routes = [
            'accounting.fixed-assets.register',
            'accounting.fixed-assets.categories',
            'accounting.fixed-assets.depreciation-runs',
            'accounting.fixed-assets.disposals',
            'accounting.fixed-assets.transfers',
            'accounting.fixed-assets.revaluations',
            'accounting.fixed-assets.impairments',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->user)->get(route($route));
            $response->assertStatus(200);
        }
    }
}

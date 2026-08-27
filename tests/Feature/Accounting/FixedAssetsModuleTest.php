<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\CostCenter;
use App\Models\DefaultAccountMapping;
use App\Models\Module;
use App\Models\User;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaCategory;
use App\Models\FixedAssets\FaClass;
use App\Models\FixedAssets\FaDepRun;
use App\Models\FixedAssets\FaDisposal;
use App\Models\FixedAssets\FaImpairment;
use App\Models\FixedAssets\FaRevaluation;
use App\Models\FixedAssets\FaTransfer;
use App\Services\Admin\NumberingSequenceService;
use App\Services\FixedAssets\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FixedAssetsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $assetAccount;
    protected Account $accumDepAccount;
    protected Account $depExpenseAccount;
    protected Account $disposalAccount;
    protected Account $apAccount;
    protected Account $bankAccount;
    protected FaCategory $category;
    protected FaClass $class;
    protected Branch $branch;
    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();

        $this->company = Company::create([
            'company_code' => 'FAMOD',
            'name' => 'FA Module Test',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->actingAs($this->user);

        session(['current_company_id' => $this->company->id]);

        app(NumberingSequenceService::class)->seedDefaults($this->company->id);

        $fiscalYear = FiscalYear::create([
            'company_id' => $this->company->id,
            'label' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'fiscal_year_id' => $fiscalYear->id,
            'label' => 'Aug 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
        ]);

        $this->assetAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1700', 'name' => 'Office Equipment',
            'type' => 'asset', 'sub_type' => 'fixed_asset', 'is_active' => true,
        ]);
        $this->accumDepAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1750', 'name' => 'Accum Dep',
            'type' => 'asset', 'sub_type' => 'accumulated_depreciation', 'is_active' => true,
        ]);
        $this->depExpenseAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '6400', 'name' => 'Dep Expense',
            'type' => 'expense', 'sub_type' => 'operating_expense', 'is_active' => true,
        ]);
        $this->disposalAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '7100', 'name' => 'Gain/Loss on Disposal',
            'type' => 'income', 'sub_type' => 'other_income', 'is_active' => true,
        ]);
        $this->apAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '2000', 'name' => 'Accounts Payable',
            'type' => 'liability', 'sub_type' => 'accounts_payable', 'is_active' => true,
        ]);
        $this->bankAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1000', 'name' => 'Bank',
            'type' => 'asset', 'sub_type' => 'bank_account', 'is_active' => true,
        ]);

        DefaultAccountMapping::setMapping($this->company->id, 'default_bank', $this->bankAccount->id);
        DefaultAccountMapping::setMapping($this->company->id, 'accounts_payable', $this->apAccount->id);

        $this->category = FaCategory::create([
            'company_id' => $this->company->id, 'name' => 'Office Equipment', 'code' => 'OE', 'is_active' => true,
        ]);
        $this->class = FaClass::create([
            'company_id' => $this->company->id, 'name' => 'Class A', 'code' => 'CA',
            'default_useful_life' => 60, 'default_residual_pct' => 10, 'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id, 'name' => 'Head Office', 'code' => 'HO', 'is_active' => true,
        ]);
        $this->costCenter = CostCenter::create([
            'company_id' => $this->company->id, 'name' => 'IT', 'code' => 'IT01', 'is_active' => true,
        ]);

        CompanyModule::updateOrCreate(
            ['company_id' => $this->company->id, 'module_id' => Module::where('code', 'fixed_assets')->first()->id],
            ['is_active' => true]
        );
    }

    protected function createAsset(array $overrides = []): FaAsset
    {
        return app(AssetService::class)->create(array_merge([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'class_id' => $this->class->id,
            'name' => 'Test Laptop',
            'acquisition_date' => '2026-08-01',
            'acquisition_cost' => 10000,
            'depreciation_method' => 'straight_line',
            'useful_life' => 24,
            'residual_value' => 1000,
            'asset_account_id' => $this->assetAccount->id,
            'accum_dep_account_id' => $this->accumDepAccount->id,
            'dep_expense_account_id' => $this->depExpenseAccount->id,
            'disposal_account_id' => $this->disposalAccount->id,
        ], $overrides), $this->user->id);
    }

    // ── Register ──────────────────────────────────

    public function test_register_renders_with_stats(): void
    {
        $this->createAsset(['name' => 'Asset One']);
        $response = $this->get(route('accounting.fixed-assets.register'));

        $response->assertOk();
        $response->assertSee('Asset Register');
        $response->assertSee('Asset One');
    }

    public function test_register_filters_by_status(): void
    {
        $this->createAsset(['name' => 'Draft Asset']);

        $response = $this->get(route('accounting.fixed-assets.register', ['status' => 'draft']));
        $response->assertOk();
        $response->assertSee('Draft Asset');

        $response2 = $this->get(route('accounting.fixed-assets.register', ['status' => 'active']));
        $response2->assertOk();
        $response2->assertDontSee('Draft Asset');
    }

    public function test_register_searches_by_name(): void
    {
        $this->createAsset(['name' => 'MacBook Pro']);

        $response = $this->get(route('accounting.fixed-assets.register', ['search' => 'MacBook']));
        $response->assertOk();
        $response->assertSee('MacBook Pro');
    }

    // ── Asset Store ────────────────────────────────

    public function test_asset_store_creates_asset(): void
    {
        $response = $this->post(route('accounting.fixed-assets.store'), [
            'category_id' => $this->category->id,
            'class_id' => $this->class->id,
            'name' => 'New Desktop',
            'acquisition_date' => '2026-08-15',
            'acquisition_cost' => 5000,
            'depreciation_method' => 'straight_line',
            'useful_life' => 36,
            'residual_value' => 500,
            'asset_account_id' => $this->assetAccount->id,
            'accum_dep_account_id' => $this->accumDepAccount->id,
            'dep_expense_account_id' => $this->depExpenseAccount->id,
            'disposal_account_id' => $this->disposalAccount->id,
            'branch_id' => $this->branch->id,
            'cost_center_id' => $this->costCenter->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_assets', [
            'company_id' => $this->company->id,
            'name' => 'New Desktop',
            'status' => 'draft',
            'acquisition_cost' => 5000,
        ]);
    }

    public function test_asset_store_validates_required_fields(): void
    {
        $response = $this->post(route('accounting.fixed-assets.store'), []);

        $response->assertSessionHasErrors(['name', 'category_id', 'acquisition_date', 'acquisition_cost']);
    }

    public function test_asset_store_validates_depreciation_method(): void
    {
        $response = $this->post(route('accounting.fixed-assets.store'), [
            'category_id' => $this->category->id,
            'name' => 'Test',
            'acquisition_date' => '2026-08-01',
            'acquisition_cost' => 1000,
            'depreciation_method' => 'invalid_method',
            'useful_life' => 12,
            'asset_account_id' => $this->assetAccount->id,
            'accum_dep_account_id' => $this->accumDepAccount->id,
            'dep_expense_account_id' => $this->depExpenseAccount->id,
        ]);

        $response->assertSessionHasErrors(['depreciation_method']);
    }

    // ── Asset Show ─────────────────────────────────

    public function test_asset_show_renders(): void
    {
        $asset = $this->createAsset();
        $response = $this->get(route('accounting.fixed-assets.show', $asset->id));

        $response->assertOk();
        $response->assertSee($asset->name);
        $response->assertSee($asset->asset_code);
        $response->assertSee('Acquisition Cost');
        $response->assertSee('Net Book Value');
    }

    public function test_asset_show_requires_company_membership(): void
    {
        $asset = $this->createAsset();
        $otherCompany = Company::create([
            'company_code' => 'OTHER', 'name' => 'Other',
            'base_currency' => 'USD', 'fiscal_year_start_month' => 1,
        ]);

        session(['current_company_id' => $otherCompany->id]);

        $response = $this->get(route('accounting.fixed-assets.show', $asset->id));
        $response->assertStatus(302);
    }

    // ── Asset Edit / Update ────────────────────────

    public function test_asset_edit_renders_form(): void
    {
        $asset = $this->createAsset();
        $response = $this->get(route('accounting.fixed-assets.edit', $asset->id));

        $response->assertOk();
        $response->assertSee($asset->name);
        $response->assertSee('Save Changes');
    }

    public function test_asset_update_persists(): void
    {
        $asset = $this->createAsset();
        $response = $this->put(route('accounting.fixed-assets.update', $asset->id), [
            'category_id' => $this->category->id,
            'name' => 'Updated Laptop',
            'acquisition_date' => '2026-08-01',
            'acquisition_cost' => 12000,
            'depreciation_method' => 'straight_line',
            'useful_life' => 36,
            'asset_account_id' => $this->assetAccount->id,
            'accum_dep_account_id' => $this->accumDepAccount->id,
            'dep_expense_account_id' => $this->depExpenseAccount->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_assets', ['id' => $asset->id, 'name' => 'Updated Laptop']);
    }

    // ── Asset Activate ─────────────────────────────

    public function test_asset_activate(): void
    {
        $asset = $this->createAsset();
        $response = $this->post(route('accounting.fixed-assets.activate', $asset->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_assets', ['id' => $asset->id, 'status' => 'active']);
    }

    // ── Asset Destroy ──────────────────────────────

    public function test_asset_destroy_draft(): void
    {
        $asset = $this->createAsset();
        $response = $this->delete(route('accounting.fixed-assets.destroy', $asset->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('fa_assets', ['id' => $asset->id]);
    }

    // ── Categories ─────────────────────────────────

    public function test_categories_list_renders(): void
    {
        $response = $this->get(route('accounting.fixed-assets.categories'));
        $response->assertOk();
        $response->assertSee('Office Equipment');
    }

    public function test_category_store(): void
    {
        $response = $this->post(route('accounting.fixed-assets.categories.store'), [
            'code' => 'VEH',
            'name' => 'Vehicles',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_categories', [
            'company_id' => $this->company->id,
            'code' => 'VEH',
            'name' => 'Vehicles',
        ]);
    }

    public function test_category_update(): void
    {
        $response = $this->put(route('accounting.fixed-assets.categories.update', $this->category->id), [
            'code' => $this->category->code,
            'name' => 'Office Equip Updated',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_categories', ['id' => $this->category->id, 'name' => 'Office Equip Updated']);
    }

    public function test_category_toggle(): void
    {
        $this->assertTrue($this->category->is_active);

        $response = $this->post(route('accounting.fixed-assets.categories.toggle', $this->category->id));
        $response->assertRedirect();

        $this->category->refresh();
        $this->assertFalse($this->category->is_active);
    }

    // ── Depreciation Runs ──────────────────────────

    public function test_depreciation_runs_list_renders(): void
    {
        $response = $this->get(route('accounting.fixed-assets.depreciation-runs'));
        $response->assertOk();
    }

    public function test_depreciation_run_create_renders(): void
    {
        $response = $this->get(route('accounting.fixed-assets.depreciation-runs.create'));
        $response->assertOk();
        $response->assertSee('New Depreciation Run');
    }

    public function test_depreciation_run_store(): void
    {
        $response = $this->post(route('accounting.fixed-assets.depreciation-runs.store'), [
            'period' => 'Aug 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'book_type' => 'financial',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_dep_runs', [
            'company_id' => $this->company->id,
            'period' => 'Aug 2026',
            'status' => 'draft',
        ]);
    }

    // ── Disposals ──────────────────────────────────

    public function test_disposals_list_renders(): void
    {
        $response = $this->get(route('accounting.fixed-assets.disposals'));
        $response->assertOk();
    }

    public function test_disposal_create_renders(): void
    {
        $asset = $this->createAsset();
        $response = $this->get(route('accounting.fixed-assets.disposals.create', $asset->id));
        $response->assertOk();
        $response->assertSee('Request Disposal');
    }

    public function test_disposal_store(): void
    {
        $asset = $this->createAsset();
        $response = $this->post(route('accounting.fixed-assets.disposals.store', $asset->id), [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'sale',
            'proceeds_amount' => 8000,
            'reason' => 'No longer needed',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_disposals', [
            'asset_id' => $asset->id,
            'disposal_method' => 'sale',
            'status' => 'pending',
        ]);
    }

    public function test_disposal_approve(): void
    {
        $asset = $this->createAsset();
        $this->post(route('accounting.fixed-assets.activate', $asset->id));
        $asset->refresh();
        $this->assertEquals('active', $asset->status);

        $service = app(\App\Services\FixedAssets\DisposalService::class);
        $disposal = $service->createDisposal($asset, [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'sale',
            'proceeds_amount' => 8000,
        ], $this->user->id);

        $response = $this->post(route('accounting.fixed-assets.disposals.approve', $disposal->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('fa_disposals', ['id' => $disposal->id, 'status' => 'approved']);
        $asset->refresh();
        $this->assertEquals('disposed', $asset->status);
    }

    public function test_disposal_reject(): void
    {
        $asset = $this->createAsset();
        $this->post(route('accounting.fixed-assets.activate', $asset->id));
        $asset->refresh();
        $this->assertEquals('active', $asset->status);

        $service = app(\App\Services\FixedAssets\DisposalService::class);
        $disposal = $service->createDisposal($asset, [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'scrap',
        ], $this->user->id);

        $response = $this->post(route('accounting.fixed-assets.disposals.reject', $disposal->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('fa_disposals', ['id' => $disposal->id, 'status' => 'rejected']);
        $asset->refresh();
        $this->assertEquals('active', $asset->status);
    }

    // ── Transfers ──────────────────────────────────

    public function test_transfers_list_renders(): void
    {
        $response = $this->get(route('accounting.fixed-assets.transfers'));
        $response->assertOk();
    }

    public function test_transfer_create_renders(): void
    {
        $asset = $this->createAsset();
        $response = $this->get(route('accounting.fixed-assets.transfers.create', $asset->id));
        $response->assertOk();
    }

    public function test_transfer_store(): void
    {
        $asset = $this->createAsset();
        $response = $this->post(route('accounting.fixed-assets.transfers.store', $asset->id), [
            'transfer_date' => '2026-08-15',
            'to_branch_id' => $this->branch->id,
            'to_cost_center_id' => $this->costCenter->id,
            'reason' => 'Relocation',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_transfers', [
            'asset_id' => $asset->id,
            'status' => 'pending',
        ]);
    }

    public function test_transfer_approve(): void
    {
        $asset = $this->createAsset();
        $service = app(AssetService::class);
        $transfer = $service->transfer($asset, [
            'transfer_date' => '2026-08-15',
            'to_branch_id' => $this->branch->id,
        ], $this->user->id);

        $response = $this->post(route('accounting.fixed-assets.transfers.approve', $transfer->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('fa_transfers', ['id' => $transfer->id, 'status' => 'approved']);
    }

    public function test_transfer_reject(): void
    {
        $asset = $this->createAsset();
        $service = app(AssetService::class);
        $transfer = $service->transfer($asset, [
            'transfer_date' => '2026-08-15',
        ], $this->user->id);

        $response = $this->post(route('accounting.fixed-assets.transfers.reject', $transfer->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('fa_transfers', ['id' => $transfer->id, 'status' => 'rejected']);
    }

    // ── Impairments ────────────────────────────────

    public function test_impairments_list_renders(): void
    {
        $response = $this->get(route('accounting.fixed-assets.impairments'));
        $response->assertOk();
    }

    public function test_impairment_create_renders(): void
    {
        $asset = $this->createAsset();
        $response = $this->get(route('accounting.fixed-assets.impairments.create', $asset->id));
        $response->assertOk();
    }

    public function test_impairment_store(): void
    {
        $asset = $this->createAsset();
        $response = $this->post(route('accounting.fixed-assets.impairments.store', $asset->id), [
            'impairment_date' => '2026-08-15',
            'recoverable_amount' => 5000,
            'reason' => 'Market decline',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_impairments', [
            'asset_id' => $asset->id,
            'status' => 'pending',
        ]);
    }

    public function test_impairment_approve(): void
    {
        $asset = $this->createAsset();
        $service = app(\App\Services\FixedAssets\DisposalService::class);
        $impairment = $service->createImpairment($asset, [
            'impairment_date' => '2026-08-15',
            'recoverable_amount' => 5000,
            'reason' => 'Market decline',
        ], $this->user->id);

        $response = $this->post(route('accounting.fixed-assets.impairments.approve', $impairment->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('fa_impairments', ['id' => $impairment->id, 'status' => 'approved']);
    }

    // ── Revaluations ───────────────────────────────

    public function test_revaluations_list_renders(): void
    {
        $response = $this->get(route('accounting.fixed-assets.revaluations'));
        $response->assertOk();
    }

    public function test_revaluation_create_renders(): void
    {
        $asset = $this->createAsset();
        $response = $this->get(route('accounting.fixed-assets.revaluations.create', $asset->id));
        $response->assertOk();
    }

    public function test_revaluation_store(): void
    {
        $asset = $this->createAsset();
        $response = $this->post(route('accounting.fixed-assets.revaluations.store', $asset->id), [
            'revaluation_date' => '2026-08-15',
            'new_value' => 15000,
            'reason' => 'Market appreciation',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fa_revaluations', [
            'asset_id' => $asset->id,
            'status' => 'pending',
        ]);
    }

    public function test_revaluation_approve(): void
    {
        $asset = $this->createAsset();
        $service = app(\App\Services\FixedAssets\DisposalService::class);
        $revaluation = $service->createRevaluation($asset, [
            'revaluation_date' => '2026-08-15',
            'new_value' => 15000,
            'reason' => 'Appreciation',
        ], $this->user->id);

        $response = $this->post(route('accounting.fixed-assets.revaluations.approve', $revaluation->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('fa_revaluations', ['id' => $revaluation->id, 'status' => 'approved']);
    }

    public function test_revaluation_reject(): void
    {
        $asset = $this->createAsset();
        $service = app(\App\Services\FixedAssets\DisposalService::class);
        $revaluation = $service->createRevaluation($asset, [
            'revaluation_date' => '2026-08-15',
            'new_value' => 15000,
            'reason' => 'Appreciation',
        ], $this->user->id);

        $response = $this->post(route('accounting.fixed-assets.revaluations.reject', $revaluation->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('fa_revaluations', ['id' => $revaluation->id, 'status' => 'rejected']);
    }

    // ── Feature Gate ───────────────────────────────

    public function test_feature_disabled_returns_404(): void
    {
        CompanyModule::where('company_id', $this->company->id)
            ->where('module_id', Module::where('code', 'fixed_assets')->first()->id)
            ->update(['is_active' => false]);

        $response = $this->get(route('accounting.fixed-assets.register'));
        $response->assertNotFound();
    }
}

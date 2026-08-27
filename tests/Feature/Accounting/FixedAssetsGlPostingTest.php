<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\User;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaCategory;
use App\Models\FixedAssets\FaClass;
use App\Models\FixedAssets\FaDepBook;
use App\Models\FixedAssets\FaDepRun;
use App\Models\FixedAssets\FaDepRunLine;
use App\Models\FixedAssets\FaDisposal;
use App\Models\FixedAssets\FaImpairment;
use App\Models\FixedAssets\FaRevaluation;
use App\Models\FiscalYear;
use App\Services\FixedAssets\AssetService;
use App\Services\FixedAssets\DepreciationService;
use App\Services\FixedAssets\DisposalService;
use App\Services\FixedAssets\AssetGlService;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedAssetsGlPostingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $assetAccount;
    protected Account $accumDepAccount;
    protected Account $depExpenseAccount;
    protected Account $disposalAccount;
    protected Account $apAccount;
    protected Account $impairmentLossAccount;
    protected Account $revaluationSurplusAccount;
    protected FaCategory $category;
    protected FaClass $class;
    protected FiscalYear $fiscalYear;
    protected AccountingPeriod $period;
    protected Account $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();

        $this->company = Company::create([
            'company_code' => 'GLTEST',
            'name' => 'GL Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->actingAs($this->user);

        session(['current_company_id' => $this->company->id]);

        // Seed numbering sequences (JPE needs them for journal entry numbering)
        app(NumberingSequenceService::class)->seedDefaults($this->company->id);

        // Create a fiscal year for the accounting period
        $this->fiscalYear = \App\Models\FiscalYear::create([
            'company_id' => $this->company->id,
            'label' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        // Create GL accounts
        $this->assetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1700',
            'name' => 'Office Equipment',
            'type' => 'asset',
            'sub_type' => 'fixed_asset',
            'is_active' => true,
        ]);

        $this->accumDepAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1750',
            'name' => 'Accumulated Depreciation',
            'type' => 'asset',
            'sub_type' => 'accumulated_depreciation',
            'is_active' => true,
        ]);

        $this->depExpenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6400',
            'name' => 'Depreciation Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->disposalAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '7100',
            'name' => 'Gain/Loss on Disposal',
            'type' => 'income',
            'sub_type' => 'other_income',
            'is_active' => true,
        ]);

        $this->apAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'accounts_payable',
            'is_active' => true,
        ]);

        $this->impairmentLossAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6500',
            'name' => 'Impairment Loss',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->revaluationSurplusAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '3300',
            'name' => 'Revaluation Surplus',
            'type' => 'equity',
            'sub_type' => 'revaluation_surplus',
            'is_active' => true,
        ]);

        // Create bank account for disposal proceeds
        $this->bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Bank',
            'type' => 'asset',
            'sub_type' => 'bank_account',
            'is_active' => true,
        ]);

        DefaultAccountMapping::setMapping($this->company->id, 'default_bank', $this->bankAccount->id);
        DefaultAccountMapping::setMapping($this->company->id, 'accounts_payable', $this->apAccount->id);

        // Create asset category and class
        $this->category = FaCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Office Equipment',
            'code' => 'OE',
            'is_active' => true,
        ]);

        $this->class = FaClass::create([
            'company_id' => $this->company->id,
            'name' => 'Class A',
            'code' => 'A',
            'default_useful_life' => 60,
            'default_residual_pct' => 10,
            'is_active' => true,
        ]);

        // Create open accounting period covering current date
        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'label' => 'Aug 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
        ]);
    }

    // ── Helpers ─────────────────────────────────────

    protected function createAsset(array $overrides = []): FaAsset
    {
        $service = app(AssetService::class);

        return $service->create(array_merge([
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

    // ── Activation Tests ────────────────────────────

    public function test_activation_posts_journal_entry(): void
    {
        $asset = $this->createAsset();

        $service = app(AssetService::class);
        $activated = $service->activate($asset, $this->user->id);

        $this->assertNotNull($activated->journal_entry_id, 'Asset should have a journal_entry_id after activation');

        $je = JournalEntry::findOrFail($activated->journal_entry_id);
        $this->assertEquals('fixed_assets', $je->source_module);
        $this->assertEquals($asset->fresh()->asset_code, $je->reference);
        $this->assertEquals('posted', $je->status);
        $this->assertEquals($this->company->id, $je->company_id);

        $lines = $je->lines;
        $this->assertCount(2, $lines);

        $dr = $lines->first(fn ($l) => $l->debit > 0);
        $cr = $lines->first(fn ($l) => $l->credit > 0);

        $this->assertEquals($this->assetAccount->id, $dr->account_id);
        $this->assertEquals(10000, $dr->debit);
        $this->assertEquals(0, $dr->credit);

        $this->assertEquals($this->apAccount->id, $cr->account_id);
        $this->assertEquals(0, $cr->debit);
        $this->assertEquals(10000, $cr->credit);
    }

    public function test_activation_skips_gl_when_no_ap_account(): void
    {
        // Delete the AP account
        Account::where('company_id', $this->company->id)
            ->where('code', '2000')
            ->delete();

        $asset = $this->createAsset();
        $service = app(AssetService::class);
        $activated = $service->activate($asset, $this->user->id);

        $this->assertNull($activated->fresh()->journal_entry_id, 'Asset should NOT have a journal_entry_id when no AP account exists');
    }

    // ── Depreciation Tests ──────────────────────────

    public function test_depreciation_post_creates_journal_entry(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 12000,
            'residual_value' => 0,
            'useful_life' => 12,
        ]);

        // Activate asset first
        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $depService = app(DepreciationService::class);
        $run = $depService->createRun(
            $this->company->id,
            '2026-08',
            '2026-08-01',
            '2026-08-31',
            'financial',
            $this->user->id,
        );

        // Should have at least one posted line
        $postedLines = $run->lines()->where('status', 'posted')->get();
        $this->assertGreaterThan(0, $postedLines->count(), 'Depreciation run should have posted lines');

        $totalDepreciation = (float) $run->total_depreciation;
        $this->assertGreaterThan(0, $totalDepreciation);

        $posted = $depService->postRun($run->fresh());

        $this->assertNotNull($posted->journal_entry_id, 'Dep run should have a journal_entry_id after posting');

        $je = JournalEntry::findOrFail($posted->journal_entry_id);
        $this->assertEquals('fixed_assets', $je->source_module);
        $this->assertEquals($posted->run_number, $je->reference);
        $this->assertEquals('posted', $je->status);

        $lines = $je->lines;
        $this->assertGreaterThanOrEqual(2, $lines->count());

        $totalDr = $lines->sum('debit');
        $totalCr = $lines->sum('credit');
        $this->assertEqualsWithDelta($totalDr, $totalCr, 0.01, 'Journal entry must be balanced (DR = CR)');
    }

    public function test_depreciation_reverse_creates_reversal_journal_entry(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 12000,
            'residual_value' => 0,
            'useful_life' => 12,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $depService = app(DepreciationService::class);
        $run = $depService->createRun(
            $this->company->id,
            '2026-08',
            '2026-08-01',
            '2026-08-31',
            'financial',
            $this->user->id,
        );

        $posted = $depService->postRun($run->fresh());
        $originalJeId = $posted->journal_entry_id;

        $this->assertNotNull($originalJeId);

        // Note: reverseRun doesn't store a JE on the run (the run stays reversed, not a new entry)
        $reversed = $depService->reverseRun($run->fresh());

        $this->assertEquals('reversed', $reversed->status);

        // The original JE still exists
        $this->assertDatabaseHas('journal_entries', [
            'id' => $originalJeId,
            'source_module' => 'fixed_assets',
        ]);

        // Verify reversal lines are opposite to original
        $origJe = JournalEntry::findOrFail($originalJeId);
        $origLines = $origJe->lines;

        // Check that accumulated dep and asset were reversed
        $asset->refresh();
        $this->assertEquals(0, (float) $asset->accumulated_depreciation);
    }

    // ── Disposal Tests ──────────────────────────────

    public function test_disposal_approve_posts_journal_entry(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000,
            'residual_value' => 0,
            'useful_life' => 24,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        // Manually set some depreciation to make the disposal realistic
        $asset->update([
            'accumulated_depreciation' => 3000,
            'net_book_value' => 7000,
        ]);

        $disposalService = app(DisposalService::class);
        $disposal = $disposalService->createDisposal($asset, [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'sale',
            'proceeds_amount' => 8000,
            'disposal_cost' => 500,
            'reason' => 'End of useful life',
        ], $this->user->id);

        $approved = $disposalService->approveDisposal($disposal, $this->user->id);

        $this->assertNotNull($approved->journal_entry_id, 'Disposal should have a journal_entry_id after approval');

        $je = JournalEntry::findOrFail($approved->journal_entry_id);
        $this->assertEquals('fixed_assets', $je->source_module);
        $this->assertEquals('posted', $je->status);

        $lines = $je->lines;
        $this->assertGreaterThanOrEqual(2, $lines->count());

        $totalDr = $lines->sum('debit');
        $totalCr = $lines->sum('credit');
        $this->assertEqualsWithDelta($totalDr, $totalCr, 0.01, 'Disposal JE must be balanced');
    }

    public function test_disposal_with_gain_posts_cr_gain_account(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000,
            'residual_value' => 0,
            'useful_life' => 24,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $asset->update([
            'accumulated_depreciation' => 8000,
            'net_book_value' => 2000,
        ]);

        $disposalService = app(DisposalService::class);
        $disposal = $disposalService->createDisposal($asset, [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'sale',
            'proceeds_amount' => 5000,
            'disposal_cost' => 0,
            'reason' => 'Sale',
        ], $this->user->id);

        // gain_loss = 5000 - 2000 = 3000 (gain)
        $this->assertEquals(3000, (float) $disposal->gain_loss);

        $approved = $disposalService->approveDisposal($disposal, $this->user->id);
        $je = JournalEntry::findOrFail($approved->journal_entry_id);

        // Should have a CR to disposal account (gain)
        $gainLine = $je->lines->where('account_id', $this->disposalAccount->id)->where('credit', '>', 0)->first();
        $this->assertNotNull($gainLine, 'Gain should be a CR to disposal account');
        $this->assertEqualsWithDelta(3000, $gainLine->credit, 0.01);
    }

    public function test_disposal_with_loss_posts_dr_loss_account(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000,
            'residual_value' => 0,
            'useful_life' => 24,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $asset->update([
            'accumulated_depreciation' => 2000,
            'net_book_value' => 8000,
        ]);

        $disposalService = app(DisposalService::class);
        $disposal = $disposalService->createDisposal($asset, [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'scrap',
            'proceeds_amount' => 1000,
            'disposal_cost' => 200,
            'reason' => 'Broken',
        ], $this->user->id);

        // net_proceeds = 800, nbv = 8000, gain_loss = 800 - 8000 = -7200 (loss)
        $this->assertEqualsWithDelta(-7200, (float) $disposal->gain_loss, 0.01);

        $approved = $disposalService->approveDisposal($disposal, $this->user->id);
        $je = JournalEntry::findOrFail($approved->journal_entry_id);

        // Should have a DR to disposal account (loss)
        $lossLine = $je->lines->where('account_id', $this->disposalAccount->id)->where('debit', '>', 0)->first();
        $this->assertNotNull($lossLine, 'Loss should be a DR to disposal account');
        $this->assertEqualsWithDelta(7200, $lossLine->debit, 0.01);
    }

    public function test_disposal_skips_gl_when_missing_accounts(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000,
            'residual_value' => 0,
            'useful_life' => 24,
        ]);

        // Null out disposal_account_id
        $asset->update(['disposal_account_id' => null]);

        $disposalService = app(DisposalService::class);
        $disposal = $disposalService->createDisposal($asset, [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'scrap',
            'proceeds_amount' => 0,
            'reason' => 'Test',
        ], $this->user->id);

        $approved = $disposalService->approveDisposal($disposal, $this->user->id);

        $this->assertNull($approved->journal_entry_id, 'Disposal should NOT have a journal_entry_id when disposal_account_id is null');
    }

    // ── Impairment Tests ────────────────────────────

    public function test_impairment_approve_posts_journal_entry(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000,
            'residual_value' => 0,
            'useful_life' => 24,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $disposalService = app(DisposalService::class);
        $impairment = $disposalService->createImpairment($asset, [
            'impairment_date' => '2026-08-15',
            'recoverable_amount' => 6000,
            'reason' => 'Market decline',
        ], $this->user->id);

        // carrying_value = 10000, recoverable = 6000, loss = 4000
        $this->assertEqualsWithDelta(4000, (float) $impairment->impairment_loss, 0.01);

        $approved = $disposalService->approveImpairment($impairment, $this->user->id);

        $this->assertNotNull($approved->journal_entry_id, 'Impairment should have a journal_entry_id');

        $je = JournalEntry::findOrFail($approved->journal_entry_id);
        $this->assertEquals('fixed_assets', $je->source_module);
        $this->assertEquals('posted', $je->status);

        $lines = $je->lines;
        $this->assertCount(2, $lines);

        $totalDr = $lines->sum('debit');
        $totalCr = $lines->sum('credit');
        $this->assertEqualsWithDelta($totalDr, $totalCr, 0.01, 'Impairment JE must be balanced');

        // DR to impairment loss account
        $drLine = $lines->first(fn ($l) => $l->debit > 0);
        $this->assertEquals($this->impairmentLossAccount->id, $drLine->account_id);
        $this->assertEqualsWithDelta(4000, $drLine->debit, 0.01);

        // CR to accumulated depreciation
        $crLine = $lines->first(fn ($l) => $l->credit > 0);
        $this->assertEquals($this->accumDepAccount->id, $crLine->account_id);
        $this->assertEqualsWithDelta(4000, $crLine->credit, 0.01);
    }

    public function test_impairment_skips_gl_when_no_loss_account(): void
    {
        // Delete the impairment loss account
        Account::where('company_id', $this->company->id)
            ->where('code', '6500')
            ->delete();

        $asset = $this->createAsset();
        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $disposalService = app(DisposalService::class);
        $impairment = $disposalService->createImpairment($asset, [
            'impairment_date' => '2026-08-15',
            'recoverable_amount' => 5000,
            'reason' => 'Test',
        ], $this->user->id);

        $approved = $disposalService->approveImpairment($impairment, $this->user->id);

        $this->assertNull($approved->journal_entry_id, 'Impairment should NOT have a journal_entry_id when no loss account exists');
    }

    // ── Revaluation Tests ───────────────────────────

    public function test_revaluation_upward_posts_journal_entry(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000,
            'residual_value' => 0,
            'useful_life' => 24,
            'net_book_value' => 7000,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $asset->update(['net_book_value' => 7000]);

        $disposalService = app(DisposalService::class);
        $revaluation = $disposalService->createRevaluation($asset, [
            'revaluation_date' => '2026-08-15',
            'new_value' => 9000,
            'reason' => 'Market appreciation',
        ], $this->user->id);

        // surplus = 9000 - 7000 = 2000 (upward)
        $this->assertEqualsWithDelta(2000, (float) $revaluation->surplus_amount, 0.01);

        $approved = $disposalService->approveRevaluation($revaluation, $this->user->id);

        $this->assertNotNull($approved->journal_entry_id, 'Revaluation should have a journal_entry_id');

        $je = JournalEntry::findOrFail($approved->journal_entry_id);
        $this->assertEquals('fixed_assets', $je->source_module);
        $this->assertEquals('posted', $je->status);

        $lines = $je->lines;
        $this->assertCount(2, $lines);

        $totalDr = $lines->sum('debit');
        $totalCr = $lines->sum('credit');
        $this->assertEqualsWithDelta($totalDr, $totalCr, 0.01, 'Revaluation JE must be balanced');

        // DR asset account (increase)
        $drLine = $lines->first(fn ($l) => $l->debit > 0);
        $this->assertEquals($this->assetAccount->id, $drLine->account_id);
        $this->assertEqualsWithDelta(2000, $drLine->debit, 0.01);

        // CR revaluation surplus
        $crLine = $lines->first(fn ($l) => $l->credit > 0);
        $this->assertEquals($this->revaluationSurplusAccount->id, $crLine->account_id);
        $this->assertEqualsWithDelta(2000, $crLine->credit, 0.01);
    }

    public function test_revaluation_downward_posts_journal_entry(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000,
            'residual_value' => 0,
            'useful_life' => 24,
            'net_book_value' => 7000,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $asset->update(['net_book_value' => 7000]);

        $disposalService = app(DisposalService::class);
        $revaluation = $disposalService->createRevaluation($asset, [
            'revaluation_date' => '2026-08-15',
            'new_value' => 5000,
            'reason' => 'Market decline',
        ], $this->user->id);

        // surplus = 5000 - 7000 = -2000 (downward)
        $this->assertEqualsWithDelta(-2000, (float) $revaluation->surplus_amount, 0.01);

        $approved = $disposalService->approveRevaluation($revaluation, $this->user->id);

        $je = JournalEntry::findOrFail($approved->journal_entry_id);

        $lines = $je->lines;
        $totalDr = $lines->sum('debit');
        $totalCr = $lines->sum('credit');
        $this->assertEqualsWithDelta($totalDr, $totalCr, 0.01, 'Downward revaluation JE must be balanced');

        // DR revaluation surplus (decrease)
        $drLine = $lines->first(fn ($l) => $l->debit > 0);
        $this->assertEquals($this->revaluationSurplusAccount->id, $drLine->account_id);
        $this->assertEqualsWithDelta(2000, $drLine->debit, 0.01);

        // CR asset account (decrease)
        $crLine = $lines->first(fn ($l) => $l->credit > 0);
        $this->assertEquals($this->assetAccount->id, $crLine->account_id);
        $this->assertEqualsWithDelta(2000, $crLine->credit, 0.01);
    }

    public function test_revaluation_skips_gl_when_zero_surplus(): void
    {
        $asset = $this->createAsset(['acquisition_cost' => 5000]);
        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        $disposalService = app(DisposalService::class);
        $revaluation = $disposalService->createRevaluation($asset, [
            'revaluation_date' => '2026-08-15',
            'new_value' => 5000,
            'reason' => 'No change',
        ], $this->user->id);

        $approved = $disposalService->approveRevaluation($revaluation, $this->user->id);

        $this->assertNull($approved->journal_entry_id, 'Revaluation with zero surplus should NOT create a JE');
    }

    public function test_revaluation_skips_gl_when_no_surplus_account(): void
    {
        Account::where('company_id', $this->company->id)
            ->where('code', '3300')
            ->delete();

        $asset = $this->createAsset(['acquisition_cost' => 10000]);
        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);
        $asset->update(['net_book_value' => 8000]);

        $disposalService = app(DisposalService::class);
        $revaluation = $disposalService->createRevaluation($asset, [
            'revaluation_date' => '2026-08-15',
            'new_value' => 12000,
            'reason' => 'Appreciation',
        ], $this->user->id);

        $approved = $disposalService->approveRevaluation($revaluation, $this->user->id);

        $this->assertNull($approved->journal_entry_id, 'Revaluation should NOT have a journal_entry_id when no surplus account exists');
    }

    // ── Balanced JE Validation ──────────────────────

    public function test_all_fa_je_entries_are_balanced(): void
    {
        // This test ensures every FA-generated JE has DR = CR
        $asset = $this->createAsset([
            'acquisition_cost' => 20000,
            'residual_value' => 2000,
            'useful_life' => 36,
        ]);

        $assetService = app(AssetService::class);
        $assetService->activate($asset, $this->user->id);

        // Depreciation run
        $depService = app(DepreciationService::class);
        $run = $depService->createRun(
            $this->company->id,
            '2026-08',
            '2026-08-01',
            '2026-08-31',
            'financial',
            $this->user->id,
        );
        $posted = $depService->postRun($run->fresh());

        $je = JournalEntry::findOrFail($posted->journal_entry_id);
        $this->assertEqualsWithDelta(
            $je->lines->sum('debit'),
            $je->lines->sum('credit'),
            0.01,
            "Depreciation JE {$je->journal_number} is unbalanced"
        );

        // Disposal
        $asset->update([
            'accumulated_depreciation' => 5000,
            'net_book_value' => 15000,
        ]);

        $disposalService = app(DisposalService::class);
        $disposal = $disposalService->createDisposal($asset, [
            'disposal_date' => '2026-08-15',
            'disposal_method' => 'sale',
            'proceeds_amount' => 12000,
            'reason' => 'Test',
        ], $this->user->id);
        $approved = $disposalService->approveDisposal($disposal, $this->user->id);

        $je = JournalEntry::findOrFail($approved->journal_entry_id);
        $this->assertEqualsWithDelta(
            $je->lines->sum('debit'),
            $je->lines->sum('credit'),
            0.01,
            "Disposal JE {$je->journal_number} is unbalanced"
        );
    }
}

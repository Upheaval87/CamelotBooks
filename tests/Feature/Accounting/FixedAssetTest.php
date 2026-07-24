<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\AccountingPeriod;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationBook;
use App\Models\AssetDisposal;
use App\Models\AssetImpairment;
use App\Models\AssetRevaluation;
use App\Models\Company;
use App\Models\DepreciationRun;
use App\Models\DepreciationScheduleEntry;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\Accounting\FixedAssetService;
use App\Services\Accounting\FixedAssets\Calculators\DoubleDecliningCalculator;
use App\Services\Accounting\FixedAssets\Calculators\ReducingBalanceCalculator;
use App\Services\Accounting\FixedAssets\Calculators\StraightLineCalculator;
use App\Services\Accounting\FixedAssets\Calculators\SumOfYearsDigitsCalculator;
use App\Services\Accounting\FixedAssets\Calculators\UnitsOfProductionCalculator;
use App\Services\Accounting\FixedAssets\DepreciationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FixedAssetTest extends TestCase
{
    use RefreshDatabase;

    protected FixedAssetService $service;

    protected DepreciationEngine $engine;

    protected Company $company;

    protected AccountingPeriod $period;

    protected int $userId;

    protected Account $assetAccount;

    protected Account $accumDepAccount;

    protected Account $deprExpAccount;

    protected Account $accumImpairmentAccount;

    protected Account $impairmentLossAccount;

    protected Account $gainLossAccount;

    protected Account $revalSurplusAccount;

    protected Account $cashAccount;

    protected Account $apAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FixedAssetService::class);
        $this->engine = app(DepreciationEngine::class);

        $user = User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->assetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1500',
            'name' => 'Fixed Assets',
            'type' => 'asset',
            'sub_type' => 'non_current_asset',
            'is_active' => true,
        ]);

        $this->accumDepAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1600',
            'name' => 'Accumulated Depreciation',
            'type' => 'asset',
            'sub_type' => 'contra_asset',
            'is_active' => true,
        ]);

        $this->deprExpAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6400',
            'name' => 'Depreciation Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->accumImpairmentAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1700',
            'name' => 'Accumulated Impairment',
            'type' => 'asset',
            'sub_type' => 'contra_asset',
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

        $this->gainLossAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '7100',
            'name' => 'Gain/Loss on Disposal',
            'type' => 'income',
            'sub_type' => 'other_income',
            'is_active' => true,
        ]);

        $this->revalSurplusAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '3300',
            'name' => 'Revaluation Surplus',
            'type' => 'equity',
            'sub_type' => 'revaluation_surplus',
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash/Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $this->apAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
    }

    protected function createAssetCategory(?int $companyId = null): AssetCategory
    {
        return AssetCategory::create([
            'company_id' => $companyId ?? $this->company->id,
            'code' => 'MACH-01',
            'name' => 'Machinery',
            'description' => 'Manufacturing machinery',
            'depreciation_method_financial' => 'straight_line',
            'useful_life_financial' => 60,
            'residual_value_type_financial' => 'amount',
            'residual_value_financial' => 1000,
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_type_tax' => 'amount',
            'residual_value_tax' => 1000,
            'depreciation_rate_tax' => null,
            'is_revaluation_enabled' => true,
            'asset_account_id' => $this->assetAccount->id,
            'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
            'depreciation_expense_account_id' => $this->deprExpAccount->id,
            'accumulated_impairment_account_id' => $this->accumImpairmentAccount->id,
            'impairment_loss_account_id' => $this->impairmentLossAccount->id,
            'disposal_gain_loss_account_id' => $this->gainLossAccount->id,
            'revaluation_surplus_account_id' => $this->revalSurplusAccount->id,
            'is_active' => true,
        ]);
    }

    protected function createAsset(
        ?int $categoryId = null,
        ?int $companyId = null,
        array $overrides = []
    ): Asset {
        $companyId = $companyId ?? $this->company->id;
        $categoryId = $categoryId ?? $this->createAssetCategory($companyId)->id;

        $data = array_merge([
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => 'Test Machine',
            'acquisition_date' => '2026-01-01',
            'in_service_date' => '2026-01-01',
            'acquisition_cost' => 10000,
            'residual_value' => 1000,
            'useful_life' => 60,
            'depreciation_method_financial' => 'straight_line',
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_tax' => 1000,
            'is_revaluation_enabled' => true,
            'asset_account_id' => $this->assetAccount->id,
            'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
            'depreciation_expense_account_id' => $this->deprExpAccount->id,
            'accumulated_impairment_account_id' => $this->accumImpairmentAccount->id,
            'impairment_loss_account_id' => $this->impairmentLossAccount->id,
            'disposal_gain_loss_account_id' => $this->gainLossAccount->id,
            'revaluation_surplus_account_id' => $this->revalSurplusAccount->id,
        ], $overrides);

        $asset = $this->service->createAsset($data, $this->userId);

        return $this->service->activateAsset($asset, $this->userId);
    }

    // ──────────────────────────────────────────────
    // Group 1: Depreciation Calculators
    // ──────────────────────────────────────────────

    public function test_straight_line_calculator(): void
    {
        $calculator = new StraightLineCalculator();

        for ($period = 1; $period <= 60; $period++) {
            $charge = $calculator->calculatePeriodCharge(
                currentCost: 10000,
                residualValue: 1000,
                accumulatedDepreciation: ($period - 1) * 150,
                accumulatedImpairment: 0,
                usefulLife: 60,
                periodNumber: $period,
            );

            $this->assertEqualsWithDelta(150.00, $charge, 0.01);
        }
    }

    public function test_reducing_balance_calculator(): void
    {
        $calculator = new ReducingBalanceCalculator();

        $charge1 = $calculator->calculatePeriodCharge(
            currentCost: 10000,
            residualValue: 0,
            accumulatedDepreciation: 0,
            accumulatedImpairment: 0,
            usefulLife: 60,
            periodNumber: 1,
            extraData: ['depreciation_rate' => 0.20],
        );
        $this->assertEqualsWithDelta(2000.00, $charge1, 0.01);

        $charge2 = $calculator->calculatePeriodCharge(
            currentCost: 10000,
            residualValue: 0,
            accumulatedDepreciation: 2000,
            accumulatedImpairment: 0,
            usefulLife: 60,
            periodNumber: 2,
            extraData: ['depreciation_rate' => 0.20],
        );
        $this->assertEqualsWithDelta(1600.00, $charge2, 0.01);

        $charge3 = $calculator->calculatePeriodCharge(
            currentCost: 10000,
            residualValue: 0,
            accumulatedDepreciation: 3600,
            accumulatedImpairment: 0,
            usefulLife: 60,
            periodNumber: 3,
            extraData: ['depreciation_rate' => 0.20],
        );
        $this->assertEqualsWithDelta(1280.00, $charge3, 0.01);
    }

    public function test_double_declining_calculator(): void
    {
        $calculator = new DoubleDecliningCalculator();

        $charge1 = $calculator->calculatePeriodCharge(
            currentCost: 10000,
            residualValue: 0,
            accumulatedDepreciation: 0,
            accumulatedImpairment: 0,
            usefulLife: 60,
            periodNumber: 1,
        );

        $this->assertEqualsWithDelta(333.33, $charge1, 0.01);
    }

    public function test_units_of_production_calculator(): void
    {
        $calculator = new UnitsOfProductionCalculator();

        $charge = $calculator->calculatePeriodCharge(
            currentCost: 10000,
            residualValue: 1000,
            accumulatedDepreciation: 0,
            accumulatedImpairment: 0,
            usefulLife: 60,
            periodNumber: 1,
            extraData: [
                'total_estimated_units' => 100000,
                'units_used' => 5000,
            ],
        );

        $this->assertEqualsWithDelta(450.00, $charge, 0.01);
    }

    public function test_syd_calculator(): void
    {
        $calculator = new SumOfYearsDigitsCalculator();

        $charge1 = $calculator->calculatePeriodCharge(
            currentCost: 10000,
            residualValue: 0,
            accumulatedDepreciation: 0,
            accumulatedImpairment: 0,
            usefulLife: 60,
            periodNumber: 1,
        );

        $expected = (60 / (60 * 61 / 2)) * 10000;
        $this->assertEqualsWithDelta(round($expected, 2), $charge1, 0.01);
        $this->assertGreaterThan(0, $charge1);
    }

    // ──────────────────────────────────────────────
    // Group 2: Asset Category + Asset CRUD
    // ──────────────────────────────────────────────

    public function test_create_asset_category(): void
    {
        $category = $this->createAssetCategory();

        $this->assertNotNull($category->id);
        $this->assertEquals('MACH-01', $category->code);
        $this->assertEquals('Machinery', $category->name);
        $this->assertEquals('straight_line', $category->depreciation_method_financial);
        $this->assertEquals(60, $category->useful_life_financial);
        $this->assertEquals($this->assetAccount->id, $category->asset_account_id);
        $this->assertEquals($this->accumDepAccount->id, $category->accumulated_depreciation_account_id);
        $this->assertEquals($this->deprExpAccount->id, $category->depreciation_expense_account_id);
        $this->assertTrue($category->is_active);
        $this->assertTrue($category->is_revaluation_enabled);
    }

    public function test_create_asset_in_draft_status(): void
    {
        $category = $this->createAssetCategory();

        $asset = $this->service->createAsset([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'New Machine',
            'acquisition_date' => '2026-01-01',
            'in_service_date' => '2026-01-01',
            'acquisition_cost' => 10000,
            'residual_value' => 1000,
            'useful_life' => 60,
            'depreciation_method_financial' => 'straight_line',
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_tax' => 1000,
            'asset_account_id' => $this->assetAccount->id,
            'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
            'depreciation_expense_account_id' => $this->deprExpAccount->id,
            'accumulated_impairment_account_id' => $this->accumImpairmentAccount->id,
            'impairment_loss_account_id' => $this->impairmentLossAccount->id,
            'disposal_gain_loss_account_id' => $this->gainLossAccount->id,
            'revaluation_surplus_account_id' => $this->revalSurplusAccount->id,
        ], $this->userId);

        $this->assertEquals(Asset::STATUS_DRAFT, $asset->status);
        $this->assertNotNull($asset->asset_code);
        $this->assertEquals(10000, (float) $asset->acquisition_cost);
        $this->assertEquals($category->id, $asset->category_id);
        $this->assertEquals($this->company->id, $asset->company_id);
    }

    public function test_activate_asset_creates_depreciation_books(): void
    {
        $category = $this->createAssetCategory();

        $asset = $this->service->createAsset([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'New Machine',
            'acquisition_date' => '2026-01-01',
            'in_service_date' => '2026-01-01',
            'acquisition_cost' => 10000,
            'residual_value' => 1000,
            'useful_life' => 60,
            'depreciation_method_financial' => 'straight_line',
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_tax' => 1000,
            'asset_account_id' => $this->assetAccount->id,
            'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
            'depreciation_expense_account_id' => $this->deprExpAccount->id,
            'accumulated_impairment_account_id' => $this->accumImpairmentAccount->id,
            'impairment_loss_account_id' => $this->impairmentLossAccount->id,
            'disposal_gain_loss_account_id' => $this->gainLossAccount->id,
            'revaluation_surplus_account_id' => $this->revalSurplusAccount->id,
        ], $this->userId);

        $activated = $this->service->activateAsset($asset, $this->userId);

        $this->assertEquals(Asset::STATUS_ACTIVE, $activated->status);

        $books = $activated->depreciationBooks()->get();
        $this->assertEquals(2, $books->count());

        $financialBook = $books->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)->first();
        $this->assertNotNull($financialBook);
        $this->assertEquals('straight_line', $financialBook->depreciation_method);
        $this->assertEquals(60, $financialBook->useful_life);
        $this->assertEqualsWithDelta(10000, (float) $financialBook->current_cost, 0.01);
        $this->assertEqualsWithDelta(0, (float) $financialBook->accumulated_depreciation, 0.01);
        $this->assertEqualsWithDelta(10000, (float) $financialBook->net_book_value, 0.01);

        $taxBook = $books->where('book_type', AssetDepreciationBook::BOOK_TAX)->first();
        $this->assertNotNull($taxBook);
        $this->assertEquals('straight_line', $taxBook->depreciation_method);
        $this->assertEquals(60, $taxBook->useful_life);
    }

    public function test_activate_asset_projects_schedule(): void
    {
        $category = $this->createAssetCategory();

        $asset = $this->service->createAsset([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'New Machine',
            'acquisition_date' => '2026-01-01',
            'in_service_date' => '2026-01-01',
            'acquisition_cost' => 10000,
            'residual_value' => 1000,
            'useful_life' => 60,
            'depreciation_method_financial' => 'straight_line',
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_tax' => 1000,
            'asset_account_id' => $this->assetAccount->id,
            'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
            'depreciation_expense_account_id' => $this->deprExpAccount->id,
            'accumulated_impairment_account_id' => $this->accumImpairmentAccount->id,
            'impairment_loss_account_id' => $this->impairmentLossAccount->id,
            'disposal_gain_loss_account_id' => $this->gainLossAccount->id,
            'revaluation_surplus_account_id' => $this->revalSurplusAccount->id,
        ], $this->userId);

        $activated = $this->service->activateAsset($asset, $this->userId);

        $financialBook = $activated->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        $entries = $financialBook->scheduleEntries()->orderBy('period_number')->get();

        $this->assertEquals(60, $entries->count());
        $this->assertEquals(1, $entries->first()->period_number);
        $this->assertEquals(60, $entries->last()->period_number);
        $this->assertFalse($entries->first()->is_posted);
        $this->assertEqualsWithDelta(150.00, (float) $entries->first()->depreciation_charge, 0.01);

        $taxBook = $activated->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_TAX)
            ->first();

        $taxEntries = $taxBook->scheduleEntries()->get();
        $this->assertEquals(60, $taxEntries->count());
    }

    // ──────────────────────────────────────────────
    // Group 3: Depreciation Run
    // ──────────────────────────────────────────────

    public function test_depreciation_run_posts_financial_book(): void
    {
        $asset = $this->createAsset();

        $financialBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        $run = $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $this->assertEquals(DepreciationRun::STATUS_POSTED, $run->status);
        $this->assertEquals(1, $run->assets_processed);
        $this->assertEqualsWithDelta(150.00, (float) $run->total_depreciation_amount, 0.01);
        $this->assertNotNull($run->journal_entry_id);

        $financialBook->refresh();
        $this->assertEqualsWithDelta(150.00, (float) $financialBook->accumulated_depreciation, 0.01);
        $this->assertEqualsWithDelta(9850.00, (float) $financialBook->net_book_value, 0.01);

        $postedEntry = $financialBook->scheduleEntries()
            ->where('period_number', 1)
            ->first();
        $this->assertTrue($postedEntry->is_posted);
        $this->assertNotNull($postedEntry->posted_at);

        $je = JournalEntry::find($run->journal_entry_id);
        $this->assertNotNull($je);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);
        $this->assertEquals('depreciation_run', $je->source_module);

        $lines = $je->lines()->get();
        $this->assertGreaterThanOrEqual(2, $lines->count());

        $drLine = $lines->first(fn ($l) => (float) $l->debit > 0);
        $crLine = $lines->first(fn ($l) => (float) $l->credit > 0);

        $this->assertEquals($this->deprExpAccount->id, $drLine->account_id);
        $this->assertEqualsWithDelta(150.00, (float) $drLine->debit, 0.01);

        $this->assertEquals($this->accumDepAccount->id, $crLine->account_id);
        $this->assertEqualsWithDelta(150.00, (float) $crLine->credit, 0.01);
    }

    public function test_depreciation_run_summarizes_je_by_category(): void
    {
        $category = $this->createAssetCategory();

        $this->createAsset($category->id, $this->company->id, [
            'name' => 'Machine A',
            'acquisition_cost' => 10000,
            'residual_value' => 1000,
            'useful_life' => 60,
        ]);

        $this->createAsset($category->id, $this->company->id, [
            'name' => 'Machine B',
            'acquisition_cost' => 20000,
            'residual_value' => 2000,
            'useful_life' => 60,
        ]);

        $run = $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $this->assertEquals(2, $run->assets_processed);
        $this->assertEqualsWithDelta(450.00, (float) $run->total_depreciation_amount, 0.01);

        $je = JournalEntry::find($run->journal_entry_id);
        $this->assertNotNull($je);

        $lines = $je->lines()->get();

        $drLines = $lines->where('debit', '>', 0);
        $crLines = $lines->where('credit', '>', 0);

        $this->assertEquals(1, $drLines->count());
        $this->assertEquals(1, $crLines->count());

        $this->assertEquals($this->deprExpAccount->id, $drLines->first()->account_id);
        $this->assertEqualsWithDelta(450.00, (float) $drLines->first()->debit, 0.01);

        $this->assertEquals($this->accumDepAccount->id, $crLines->first()->account_id);
        $this->assertEqualsWithDelta(450.00, (float) $crLines->first()->credit, 0.01);
    }

    public function test_depreciation_run_skips_locked_period(): void
    {
        $this->createAsset();

        $this->period->update(['status' => 'locked']);

        $run = $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $this->assertEquals(DepreciationRun::STATUS_DRAFT, $run->status);
        $this->assertEquals(0, $run->assets_processed);
        $this->assertEquals(1, $run->assets_skipped);
        $this->assertNull($run->journal_entry_id);

        $skipReasons = $run->skip_reasons;
        $this->assertNotEmpty($skipReasons);
        $this->assertEquals('Period is locked', $skipReasons[0]['reason']);
    }

    // ──────────────────────────────────────────────
    // Group 4: Disposal
    // ──────────────────────────────────────────────

    public function test_disposal_removes_asset_from_future_runs(): void
    {
        $asset = $this->createAsset();

        $this->service->createDisposal([
            'asset_id' => $asset->id,
            'company_id' => $this->company->id,
            'disposal_date' => '2026-01-15',
            'proceeds_amount' => 5000,
            'proceeds_account_id' => $this->cashAccount->id,
            'disposal_method' => 'sale',
        ], $this->userId);

        $asset->refresh();
        $this->assertEquals(Asset::STATUS_DISPOSED, $asset->status);

        $run = $this->engine->runDepreciation($this->company->id, '2026-02', $this->userId);

        $this->assertEquals(0, $run->assets_processed);
    }

    public function test_disposal_posts_correct_je(): void
    {
        $asset = $this->createAsset();

        $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $disposal = $this->service->createDisposal([
            'asset_id' => $asset->id,
            'company_id' => $this->company->id,
            'disposal_date' => '2026-02-15',
            'proceeds_amount' => 5000,
            'proceeds_account_id' => $this->cashAccount->id,
            'disposal_method' => 'sale',
        ], $this->userId);

        $this->assertInstanceOf(AssetDisposal::class, $disposal);
        $this->assertNotNull($disposal->journal_entry_id);

        $financialBook = $asset->fresh()->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        $accumDep = (float) $financialBook->accumulated_depreciation;
        $nbv = (float) $financialBook->net_book_value;
        $proceeds = 5000.00;
        $gainLoss = $proceeds - $nbv;

        $je = JournalEntry::find($disposal->journal_entry_id);
        $this->assertNotNull($je);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);

        $lines = $je->lines()->get();

        $accDepLine = $lines->where('account_id', $this->accumDepAccount->id)->first();
        $this->assertNotNull($accDepLine);
        $this->assertEqualsWithDelta($accumDep, (float) $accDepLine->debit, 0.01);

        $cashLine = $lines->where('account_id', $this->cashAccount->id)->first();
        $this->assertNotNull($cashLine);
        $this->assertEqualsWithDelta(5000.00, (float) $cashLine->debit, 0.01);

        $assetLine = $lines->where('account_id', $this->assetAccount->id)->first();
        $this->assertNotNull($assetLine);
        $this->assertEqualsWithDelta(10000.00, (float) $assetLine->credit, 0.01);

        $gainLossLine = $lines->where('account_id', $this->gainLossAccount->id)->first();
        $this->assertNotNull($gainLossLine);

        if ($gainLoss > 0) {
            $this->assertEqualsWithDelta($gainLoss, (float) $gainLossLine->credit, 0.01);
        } elseif ($gainLoss < 0) {
            $this->assertEqualsWithDelta(abs($gainLoss), (float) $gainLossLine->debit, 0.01);
        }

        $totalDebit = $lines->sum(fn ($l) => (float) $l->debit);
        $totalCredit = $lines->sum(fn ($l) => (float) $l->credit);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
    }

    // ──────────────────────────────────────────────
    // Group 5: Impairment
    // ──────────────────────────────────────────────

    public function test_impairment_posts_dr_impairment_loss_cr_accumulated_impairment(): void
    {
        $asset = $this->createAsset();

        $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $financialBook = $asset->fresh()->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();
        $nbvBefore = (float) $financialBook->net_book_value;

        $impairment = $this->service->createImpairment([
            'asset_id' => $asset->id,
            'company_id' => $this->company->id,
            'impairment_date' => '2026-01-31',
            'recoverable_amount' => $nbvBefore - 2000,
        ], $this->userId);

        $this->assertInstanceOf(AssetImpairment::class, $impairment);
        $this->assertNotNull($impairment->journal_entry_id);
        $this->assertEqualsWithDelta(2000.00, (float) $impairment->impairment_amount, 0.01);

        $je = JournalEntry::find($impairment->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);

        $lines = $je->lines()->get();

        $drLine = $lines->first(fn ($l) => (float) $l->debit > 0);
        $crLine = $lines->first(fn ($l) => (float) $l->credit > 0);

        $this->assertEquals($this->impairmentLossAccount->id, $drLine->account_id);
        $this->assertEqualsWithDelta(2000.00, (float) $drLine->debit, 0.01);

        $this->assertEquals($this->accumImpairmentAccount->id, $crLine->account_id);
        $this->assertEqualsWithDelta(2000.00, (float) $crLine->credit, 0.01);

        $financialBook->refresh();
        $this->assertEqualsWithDelta(2000.00, (float) $financialBook->accumulated_impairment, 0.01);
        $this->assertEqualsWithDelta($nbvBefore - 2000, (float) $financialBook->net_book_value, 0.01);
    }

    public function test_impairment_reversal_capped_at_pre_impairment_carrying_value(): void
    {
        $asset = $this->createAsset();

        $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $financialBook = $asset->fresh()->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();
        $nbvBeforeImpairment = (float) $financialBook->net_book_value;

        $impairment = $this->service->createImpairment([
            'asset_id' => $asset->id,
            'company_id' => $this->company->id,
            'impairment_date' => '2026-01-31',
            'recoverable_amount' => $nbvBeforeImpairment - 2000,
        ], $this->userId);

        $financialBook->refresh();
        $carryingValueAfterImpairment = (float) $financialBook->net_book_value;

        $depreciableAmount = 10000 - 1000;
        $maxReversal = $depreciableAmount - (float) $financialBook->accumulated_depreciation - $carryingValueAfterImpairment;

        $reversal = $this->service->reverseImpairment([
            'asset_id' => $asset->id,
            'company_id' => $this->company->id,
            'reversal_date' => '2026-02-28',
            'original_impairment_id' => $impairment->id,
        ], $this->userId);

        $this->assertInstanceOf(AssetImpairment::class, $reversal);
        $this->assertTrue($reversal->is_reversal);
        $this->assertEquals($impairment->id, $reversal->reversed_impairment_id);

        $reversalAmount = (float) $reversal->impairment_amount;
        $this->assertLessThanOrEqual((float) $impairment->impairment_amount, $reversalAmount);

        if ($maxReversal > 0) {
            $this->assertLessThanOrEqual($maxReversal, $reversalAmount);
        }

        $financialBook->refresh();
        $this->assertGreaterThanOrEqual(
            $carryingValueAfterImpairment,
            (float) $financialBook->net_book_value
        );
    }

    // ──────────────────────────────────────────────
    // Group 6: Revaluation
    // ──────────────────────────────────────────────

    public function test_revaluation_increase_posts_dr_asset_cr_revaluation_surplus(): void
    {
        $asset = $this->createAsset();

        $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $financialBook = $asset->fresh()->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();
        $nbvBefore = (float) $financialBook->net_book_value;

        $fairValue = $nbvBefore + 3000;

        $revaluation = $this->service->createRevaluation([
            'asset_id' => $asset->id,
            'company_id' => $this->company->id,
            'revaluation_date' => '2026-01-31',
            'fair_value' => $fairValue,
        ], $this->userId);

        $this->assertInstanceOf(AssetRevaluation::class, $revaluation);
        $this->assertNotNull($revaluation->journal_entry_id);
        $this->assertEqualsWithDelta(3000.00, (float) $revaluation->surplus_amount, 0.01);

        $je = JournalEntry::find($revaluation->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);

        $lines = $je->lines()->get();

        $drLine = $lines->first(fn ($l) => (float) $l->debit > 0);
        $crLine = $lines->first(fn ($l) => (float) $l->credit > 0);

        $this->assertEquals($this->assetAccount->id, $drLine->account_id);
        $this->assertEqualsWithDelta(3000.00, (float) $drLine->debit, 0.01);

        $this->assertEquals($this->revalSurplusAccount->id, $crLine->account_id);
        $this->assertEqualsWithDelta(3000.00, (float) $crLine->credit, 0.01);

        $financialBook->refresh();
        $this->assertEqualsWithDelta($fairValue, (float) $financialBook->net_book_value, 0.01);
        $this->assertEqualsWithDelta(10000 + 3000, (float) $financialBook->current_cost, 0.01);
    }

    // ──────────────────────────────────────────────
    // Group 7: Tax Book Isolation
    // ──────────────────────────────────────────────

    public function test_tax_book_calculations_never_affect_gl(): void
    {
        $asset = $this->createAsset();

        $taxBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_TAX)
            ->first();

        $taxEntriesBefore = $taxBook->scheduleEntries()->count();
        $this->assertGreaterThan(0, $taxEntriesBefore);

        $jeCountBefore = JournalEntry::where('source_module', 'depreciation_run')->count();

        $run = $this->engine->runDepreciation($this->company->id, '2026-01', $this->userId);

        $this->assertEquals(DepreciationRun::STATUS_POSTED, $run->status);

        $taxBook->refresh();
        $this->assertEqualsWithDelta(0, (float) $taxBook->accumulated_depreciation, 0.01);
        $this->assertEqualsWithDelta(10000, (float) $taxBook->net_book_value, 0.01);

        $taxBookSchedule = $taxBook->scheduleEntries()->where('is_posted', true)->count();
        $this->assertEquals(0, $taxBookSchedule);

        $financialBook = $asset->fresh()->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();
        $this->assertGreaterThan(0, (float) $financialBook->accumulated_depreciation);

        $this->assertEquals($jeCountBefore + 1, JournalEntry::where('source_module', 'depreciation_run')->count());
    }
}

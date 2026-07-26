<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\GrnLine;
use App\Models\InventoryCostLayer;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\LandedCostVoucher;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Accounting\GoodsReceivedNoteService;
use App\Services\Accounting\InventoryService;
use App\Services\Inventory\LandedCostAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandedCostTest extends TestCase
{
    use RefreshDatabase;

    protected LandedCostAllocationService $service;
    protected GoodsReceivedNoteService $grnService;
    protected InventoryService $inventoryService;
    protected Company $company;
    protected User $user;
    protected Vendor $vendor;
    protected AccountingPeriod $period;

    protected Account $apAccount;
    protected Account $bankAccount;
    protected Account $inventoryAssetAccount;
    protected Account $cogsAccount;
    protected Account $freightAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LandedCostAllocationService::class);
        $this->grnService = app(GoodsReceivedNoteService::class);
        $this->inventoryService = app(InventoryService::class);

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'Landed Cost Test Co',
            'company_code' => 'LC',
            'is_active' => true,
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Overseas Supplier',
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

        $this->bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash at Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->inventoryAssetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1200',
            'name' => 'Inventory Asset',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->cogsAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'Cost of Goods Sold',
            'type' => 'expense',
            'sub_type' => 'cost_of_goods_sold',
            'is_active' => true,
        ]);

        $this->freightAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2100',
            'name' => 'Freight Clearing',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '2150',
            'name' => 'Accrued Purchases',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '6800',
            'name' => 'Purchase Price Variance',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);
    }

    protected function createProduct(string $name, string $sku): Product
    {
        return Product::create([
            'company_id' => $this->company->id,
            'name' => $name,
            'sku' => $sku,
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 20.00,
            'purchase_price' => 10.00,
            'income_account_id' => Account::create([
                'company_id' => $this->company->id,
                'code' => '4000-' . $sku,
                'name' => 'Revenue ' . $sku,
                'type' => 'revenue',
                'sub_type' => 'operating_revenue',
                'is_active' => true,
            ])->id,
            'expense_account_id' => $this->cogsAccount->id,
            'inventory_asset_account_id' => $this->inventoryAssetAccount->id,
            'is_active' => true,
        ]);
    }

    protected function createGrn(Product $product, float $unitCost, float $qty, ?int $branchId = null): GoodsReceivedNote
    {
        $grn = $this->grnService->create([
            'company_id' => $this->company->id,
            'branch_id' => $branchId,
            'vendor_id' => $this->vendor->id,
            'date' => '2026-01-15',
            'lines' => [
                [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity_ordered' => $qty,
                    'quantity_received' => $qty,
                    'unit_cost' => $unitCost,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->user->id);

        return $this->grnService->post($grn, $this->user->id);
    }

    // ==========================================
    // CREATION TESTS
    // ==========================================

    public function test_create_landed_cost_voucher(): void
    {
        $product = $this->createProduct('Widget', 'WDG-01');
        $grn = $this->createGrn($product, 10.00, 100);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_value',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Ocean freight',
                    'amount' => 500.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn->id],
        ], $this->user->id);

        $this->assertEquals(LandedCostVoucher::STATUS_DRAFT, $voucher->status);
        $this->assertEquals(500.00, $voucher->total_amount);
        $this->assertEquals('by_value', $voucher->allocation_method);
        $this->assertStringStartsWith('LC-2026-', $voucher->voucher_number);
        $this->assertEquals(1, $voucher->components()->count());
        $this->assertEquals(1, $voucher->grns()->count());
    }

    public function test_create_requires_components(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'date' => '2026-01-20',
            'components' => [],
            'grn_ids' => [1],
        ], $this->user->id);
    }

    public function test_create_requires_grns(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Freight',
                    'amount' => 500,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [],
        ], $this->user->id);
    }

    // ==========================================
    // POSTING TESTS — ALL IN INVENTORY
    // ==========================================

    public function test_post_simple_landed_cost_all_in_inventory(): void
    {
        $product = $this->createProduct('Widget', 'WDG-02');
        $grn = $this->createGrn($product, 10.00, 100);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_value',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Ocean freight',
                    'amount' => 500.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn->id],
        ], $this->user->id);

        $voucher = $this->service->post($voucher, $this->user->id);

        $this->assertEquals(LandedCostVoucher::STATUS_POSTED, $voucher->status);
        $this->assertNotNull($voucher->journal_entry_id);

        $je = JournalEntry::findOrFail($voucher->journal_entry_id);
        $lines = $je->lines()->get();

        $this->assertEquals($lines->sum('debit'), $lines->sum('credit'), '', 0.01);

        $invLine = $lines->firstWhere('account_id', $this->inventoryAssetAccount->id);
        $this->assertNotNull($invLine);
        $this->assertEqualsWithDelta(500.00, (float) $invLine->debit, 0.01);

        $cogsLine = $lines->firstWhere('account_id', $this->cogsAccount->id);
        $this->assertNull($cogsLine);

        $creditLine = $lines->firstWhere('account_id', $this->freightAccount->id);
        $this->assertNotNull($creditLine);
        $this->assertEqualsWithDelta(500.00, (float) $creditLine->credit, 0.01);
    }

    public function test_post_updates_cost_layers(): void
    {
        $product = $this->createProduct('Widget', 'WDG-03');
        $grn = $this->createGrn($product, 10.00, 100);

        $layerBefore = InventoryCostLayer::where('product_id', $product->id)->first();
        $this->assertEquals(10.00, (float) $layerBefore->unit_cost);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_value',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Ocean freight',
                    'amount' => 500.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn->id],
        ], $this->user->id);

        $this->service->post($voucher, $this->user->id);

        $layerAfter = InventoryCostLayer::where('product_id', $product->id)->first();
        // unit_cost was 10.00, landed cost per unit is 500/100 = 5.00, new cost = 15.00
        $this->assertEqualsWithDelta(15.00, (float) $layerAfter->unit_cost, 0.01);
    }

    // ==========================================
    // POSTING TESTS — PARTIALLY CONSUMED
    // ==========================================

    public function test_post_landed_cost_partially_consumed(): void
    {
        $product = $this->createProduct('Widget', 'WDG-04');
        $grn = $this->createGrn($product, 10.00, 100);

        $this->inventoryService->consumeStock(
            $this->company->id,
            $product->id,
            null,
            40,
            '2026-01-18'
        );

        $stock = InventoryStock::where('product_id', $product->id)->first();
        $this->assertEquals(60.00, (float) $stock->quantity_on_hand);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_value',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Ocean freight',
                    'amount' => 500.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn->id],
        ], $this->user->id);

        $voucher = $this->service->post($voucher, $this->user->id);

        $je = JournalEntry::findOrFail($voucher->journal_entry_id);
        $lines = $je->lines()->get();

        $this->assertEquals($lines->sum('debit'), $lines->sum('credit'), '', 0.01);

        $invLine = $lines->firstWhere('account_id', $this->inventoryAssetAccount->id);
        $this->assertNotNull($invLine);

        $cogsLine = $lines->firstWhere('account_id', $this->cogsAccount->id);
        $this->assertNotNull($cogsLine);

        $totalLandedPerUnit = 500.00 / 100;
        $expectedInvAsset = round(60 * $totalLandedPerUnit, 2);
        $expectedCogs = round(40 * $totalLandedPerUnit, 2);

        $this->assertEqualsWithDelta($expectedInvAsset, (float) $invLine->debit, 0.01);
        $this->assertEqualsWithDelta($expectedCogs, (float) $cogsLine->debit, 0.01);
        $this->assertEqualsWithDelta(500.00, $expectedInvAsset + $expectedCogs, 0.01);
    }

    // ==========================================
    // POSTING TESTS — BY QUANTITY
    // ==========================================

    public function test_post_landed_cost_by_quantity(): void
    {
        $product = $this->createProduct('Widget', 'WDG-05');
        $grn = $this->createGrn($product, 10.00, 100);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_quantity',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'handling',
                    'description' => 'Warehouse handling',
                    'amount' => 300.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn->id],
        ], $this->user->id);

        $voucher = $this->service->post($voucher, $this->user->id);

        $this->assertEquals(LandedCostVoucher::STATUS_POSTED, $voucher->status);

        $layerAfter = InventoryCostLayer::where('product_id', $product->id)->first();
        // per unit = 300/100 = 3.00, new cost = 10 + 3 = 13
        $this->assertEqualsWithDelta(13.00, (float) $layerAfter->unit_cost, 0.01);
    }

    // ==========================================
    // POSTING TESTS — MULTIPLE COMPONENTS
    // ==========================================

    public function test_post_landed_cost_multiple_components(): void
    {
        $product = $this->createProduct('Widget', 'WDG-06');
        $grn = $this->createGrn($product, 10.00, 100);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_value',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Ocean freight',
                    'amount' => 500.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
                [
                    'component_type' => 'insurance',
                    'description' => 'Cargo insurance',
                    'amount' => 100.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn->id],
        ], $this->user->id);

        $voucher = $this->service->post($voucher, $this->user->id);

        $je = JournalEntry::findOrFail($voucher->journal_entry_id);
        $lines = $je->lines()->get();

        $this->assertEquals($lines->sum('debit'), $lines->sum('credit'), '', 0.01);

        $invLine = $lines->firstWhere('account_id', $this->inventoryAssetAccount->id);
        $this->assertEqualsWithDelta(600.00, (float) $invLine->debit, 0.01);

        $layerAfter = InventoryCostLayer::where('product_id', $product->id)->first();
        // per unit = 600/100 = 6.00, new cost = 10 + 6 = 16
        $this->assertEqualsWithDelta(16.00, (float) $layerAfter->unit_cost, 0.01);
    }

    // ==========================================
    // POSTING TESTS — MULTIPLE GRNs
    // ==========================================

    public function test_post_landed_cost_splits_across_grns(): void
    {
        $product = $this->createProduct('Widget', 'WDG-07');
        $grn1 = $this->createGrn($product, 10.00, 60);
        $grn2 = $this->createGrn($product, 12.00, 40);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_quantity',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Freight',
                    'amount' => 200.00,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn1->id, $grn2->id],
        ], $this->user->id);

        $voucher = $this->service->post($voucher, $this->user->id);

        $this->assertEquals(LandedCostVoucher::STATUS_POSTED, $voucher->status);

        $layers = InventoryCostLayer::where('product_id', $product->id)
            ->orderBy('unit_cost')
            ->get();

        $this->assertCount(2, $layers);

        // GRN1: 60 units → 200 * (60/100) = 120 → per unit = 120/60 = 2.00 → new = 10 + 2 = 12
        // GRN2: 40 units → 200 * (40/100) = 80 → per unit = 80/40 = 2.00 → new = 12 + 2 = 14
        $layer1 = $layers->first();
        $layer2 = $layers->last();

        $this->assertEqualsWithDelta(12.00, (float) $layer1->unit_cost, 0.01);
        $this->assertEqualsWithDelta(14.00, (float) $layer2->unit_cost, 0.01);
    }

    // ==========================================
    // VOID TEST
    // ==========================================

    public function test_cannot_post_draft_twice(): void
    {
        $product = $this->createProduct('Widget', 'WDG-08');
        $grn = $this->createGrn($product, 10.00, 100);

        $voucher = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'allocation_method' => 'by_value',
            'date' => '2026-01-20',
            'components' => [
                [
                    'component_type' => 'freight',
                    'description' => 'Freight',
                    'amount' => 500,
                    'payee_account_id' => $this->freightAccount->id,
                ],
            ],
            'grn_ids' => [$grn->id],
        ], $this->user->id);

        $this->service->post($voucher, $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->post($voucher->fresh(), $this->user->id);
    }
}

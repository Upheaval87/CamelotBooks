<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryCostLayer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\User;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private int $userId;
    private AccountingPeriod $period;
    private Account $invAssetAccount;
    private Account $cogsAccount;
    private Account $adjAccount;
    private Product $product;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->userId = $this->user->id;

        $this->company = Company::create([
            'name' => 'Inventory Test Co',
            'company_code' => 'INVTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'allow_negative_stock' => false,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->invAssetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1200',
            'name' => 'Inventory',
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

        $this->adjAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6700',
            'name' => 'Inventory Adjustment',
            'type' => 'expense',
            'sub_type' => 'cost_of_goods_sold',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget A',
            'sku' => 'WGT-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 25.00,
            'purchase_price' => 10.00,
            'income_account_id' => Account::create([
                'company_id' => $this->company->id,
                'code' => '4000',
                'name' => 'Sales Revenue',
                'type' => 'income',
                'sub_type' => 'operating_revenue',
                'is_active' => true,
            ])->id,
            'expense_account_id' => $this->cogsAccount->id,
            'inventory_asset_account_id' => $this->invAssetAccount->id,
            'is_taxable' => false,
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Warehouse',
            'code' => 'WH01',
            'is_active' => true,
        ]);
    }

    public function test_receive_stock_creates_cost_layer(): void
    {
        $service = app(InventoryService::class);

        $layer = $service->receiveStock(
            $this->company->id,
            $this->product->id,
            $this->branch->id,
            100,
            10.00,
            'bill',
            1,
            '2026-01-15'
        );

        $this->assertEquals(100, $layer->quantity_remaining);
        $this->assertEquals(10.00, $layer->unit_cost);
        $this->assertEquals('bill', $layer->source_type);
        $this->assertEquals(1, $layer->source_id);
    }

    public function test_receive_stock_updates_quantity_on_hand(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 100, 10.00, 'bill', 1, '2026-01-15');

        $onHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);

        $this->assertEquals(100, $onHand);
    }

    public function test_receive_stock_multiple_layers(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');
        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 12.00, 'bill', 2, '2026-02-15');

        $onHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);

        $this->assertEquals(100, $onHand);

        $layers = InventoryCostLayer::where('company_id', $this->company->id)
            ->where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->available()
            ->orderBy('date')
            ->get();

        $this->assertCount(2, $layers);
        $this->assertEquals(10.00, $layers[0]->unit_cost);
        $this->assertEquals(12.00, $layers[1]->unit_cost);
    }

    public function test_fifo_consumption_from_oldest_layer_first(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');
        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 12.00, 'bill', 2, '2026-02-15');

        $consumed = $service->consumeStock($this->company->id, $this->product->id, $this->branch->id, 30, '2026-03-01');

        $this->assertCount(1, $consumed);
        $this->assertEquals(30, $consumed[0]['quantity']);
        $this->assertEquals(10.00, $consumed[0]['unit_cost']);
        $this->assertEquals(300.00, $consumed[0]['total_cost']);

        $onHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);
        $this->assertEquals(70, $onHand);
    }

    public function test_fifo_consumption_spans_multiple_layers(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 20, 10.00, 'bill', 1, '2026-01-15');
        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 30, 12.00, 'bill', 2, '2026-02-15');

        $consumed = $service->consumeStock($this->company->id, $this->product->id, $this->branch->id, 40, '2026-03-01');

        $this->assertCount(2, $consumed);
        $this->assertEquals(20, $consumed[0]['quantity']);
        $this->assertEquals(10.00, $consumed[0]['unit_cost']);
        $this->assertEquals(20, $consumed[1]['quantity']);
        $this->assertEquals(12.00, $consumed[1]['unit_cost']);

        $totalCost = $consumed[0]['total_cost'] + $consumed[1]['total_cost'];
        $this->assertEquals(440.00, $totalCost);

        $onHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);
        $this->assertEquals(10, $onHand);
    }

    public function test_fifo_partial_layer_consumption(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 100, 10.00, 'bill', 1, '2026-01-15');

        $consumed = $service->consumeStock($this->company->id, $this->product->id, $this->branch->id, 30, '2026-02-01');

        $this->assertCount(1, $consumed);
        $this->assertEquals(30, $consumed[0]['quantity']);

        $layer = InventoryCostLayer::find($consumed[0]['layer_id']);
        $this->assertEquals(70, $layer->quantity_remaining);

        $onHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);
        $this->assertEquals(70, $onHand);
    }

    public function test_oversell_prevention_throws_when_insufficient_stock(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 10, 10.00, 'bill', 1, '2026-01-15');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $service->consumeStock($this->company->id, $this->product->id, $this->branch->id, 20, '2026-02-01');
    }

    public function test_oversell_allowed_with_negative_stock_flag(): void
    {
        $this->company->update(['allow_negative_stock' => true]);

        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 10, 10.00, 'bill', 1, '2026-01-15');

        $consumed = $service->consumeStock($this->company->id, $this->product->id, $this->branch->id, 20, '2026-02-01');

        $this->assertCount(1, $consumed);
        $this->assertEquals(10, $consumed[0]['quantity']);

        $onHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);
        $this->assertEquals(-10, $onHand);
    }

    public function test_get_quantity_on_hand_across_branches(): void
    {
        $branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch 2',
            'code' => 'BR02',
            'is_active' => true,
        ]);

        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');
        $service->receiveStock($this->company->id, $this->product->id, $branch2->id, 30, 10.00, 'bill', 2, '2026-01-15');

        $branch1OnHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);
        $branch2OnHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $branch2->id);
        $totalOnHand = $service->getQuantityOnHand($this->company->id, $this->product->id);

        $this->assertEquals(50, $branch1OnHand);
        $this->assertEquals(30, $branch2OnHand);
        $this->assertEquals(80, $totalOnHand);
    }

    public function test_adjustment_number_is_sequential(): void
    {
        $service = app(InventoryService::class);

        $num1 = $service->generateAdjustmentNumber($this->company->id);
        $this->assertEquals('ADJ-2026-0001', $num1);

        DB::table('inventory_adjustments')->insert([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'adjustment_number' => $num1,
            'date' => '2026-01-15',
            'type' => 'increase',
            'quantity' => 10,
            'reason_code' => 'correction',
            'status' => 'posted',
        ]);

        $num2 = $service->generateAdjustmentNumber($this->company->id);
        $this->assertEquals('ADJ-2026-0002', $num2);
    }

    public function test_transfer_number_is_sequential(): void
    {
        $service = app(InventoryService::class);

        $num1 = $service->generateTransferNumber($this->company->id);
        $this->assertEquals('TRF-2026-0001', $num1);

        DB::table('inventory_transfers')->insert([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'from_branch_id' => $this->branch->id,
            'to_branch_id' => $this->branch->id,
            'transfer_number' => $num1,
            'date' => '2026-01-15',
            'quantity' => 10,
            'status' => 'completed',
        ]);

        $num2 = $service->generateTransferNumber($this->company->id);
        $this->assertEquals('TRF-2026-0002', $num2);
    }

    public function test_stock_transfer_between_branches(): void
    {
        $branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch 2',
            'code' => 'BR02',
            'is_active' => true,
        ]);

        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');

        $transfer = $service->transferStock(
            $this->company->id,
            $this->product->id,
            $this->branch->id,
            $branch2->id,
            20,
            'Monthly transfer',
            $this->userId,
            '2026-02-01'
        );

        $this->assertEquals('completed', $transfer->status);
        $this->assertEquals(20, $transfer->quantity);

        $wh1OnHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);
        $wh2OnHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $branch2->id);

        $this->assertEquals(30, $wh1OnHand);
        $this->assertEquals(20, $wh2OnHand);
    }

    public function test_valuation_by_product(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');
        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 30, 12.00, 'bill', 2, '2026-02-15');

        $valuation = $service->getValuationByProduct($this->company->id);

        $this->assertCount(1, $valuation);
        $this->assertEquals($this->product->id, $valuation[0]['product_id']);
        $this->assertEquals(80, $valuation[0]['total_quantity']);
        $this->assertEquals(860.00, $valuation[0]['total_value']);
    }

    public function test_low_stock_detection(): void
    {
        $this->product->update(['reorder_point' => 25]);

        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 20, 10.00, 'bill', 1, '2026-01-15');

        $lowStock = $service->getLowStockItems($this->company->id);

        $this->assertCount(1, $lowStock);
        $this->assertEquals(20, $lowStock[0]['quantity_on_hand']);
        $this->assertEquals(5, $lowStock[0]['shortage']);
    }

    public function test_product_not_low_stock_when_above_reorder(): void
    {
        $this->product->update(['reorder_point' => 25]);

        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');

        $lowStock = $service->getLowStockItems($this->company->id);

        $this->assertCount(0, $lowStock);
    }

    public function test_total_inventory_asset_value(): void
    {
        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');
        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 30, 12.00, 'bill', 2, '2026-02-15');

        $totalValue = $service->getTotalInventoryAssetValue($this->company->id);

        $this->assertEquals(860.00, $totalValue);
    }

    public function test_receive_negative_quantity_throws(): void
    {
        $service = app(InventoryService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, -10, 10.00, 'bill', 1, '2026-01-15');
    }

    public function test_consume_negative_quantity_throws(): void
    {
        $service = app(InventoryService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->consumeStock($this->company->id, $this->product->id, $this->branch->id, -10, '2026-01-15');
    }

    public function test_company_isolation_for_stock(): void
    {
        $company2 = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OTHC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $service = app(InventoryService::class);

        $service->receiveStock($this->company->id, $this->product->id, $this->branch->id, 50, 10.00, 'bill', 1, '2026-01-15');
        $service->receiveStock($company2->id, $this->product->id, $this->branch->id, 100, 10.00, 'bill', 1, '2026-01-15');

        $company1OnHand = $service->getQuantityOnHand($this->company->id, $this->product->id, $this->branch->id);
        $this->assertEquals(50, $company1OnHand);
    }
}

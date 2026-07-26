<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\NumberingSequence;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\PosSettlement;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\FeatureManagement;
use App\Services\POS\PosSetupService;
use App\Services\POS\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSettlementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PosTerminal $terminal;
    private PosPaymentMethod $cardMethod;
    private Account $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'POS Settlement Co',
            'company_code' => 'PSC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        FeatureManagement::enable($this->company->id, 'pos');
        PosSetupService::seedDefaultsForCompany($this->company->id);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('F Y'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'pos_settlement',
            'prefix' => 'STL-',
            'padding_width' => 5,
            'reset_policy' => 'never',
            'next_number' => 1,
            'is_active' => true,
        ]);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Front Counter',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        $this->cardMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Card')->first();

        $this->bankAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1000'],
            ['name' => 'Cash and Cash Equivalents', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );
    }

    private function baseSettlementData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'payment_method_id' => $this->cardMethod->id,
            'bank_account_id' => $this->bankAccount->id,
            'period_start' => now()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'total_amount' => 500.00,
            'fee_amount' => 15.00,
        ], $overrides);
    }

    // =============================================
    // SERVICE TESTS
    // =============================================

    public function test_settle_creates_settlement_and_posts_je(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(),
            $this->user->id
        );

        $this->assertInstanceOf(PosSettlement::class, $settlement);
        $this->assertEquals('posted', $settlement->status);
        $this->assertNotNull($settlement->journal_entry_id);
        $this->assertEquals($this->user->id, $settlement->settled_by);
        $this->assertNotNull($settlement->settled_at);
    }

    public function test_settlement_number_generated(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(),
            $this->user->id
        );

        $this->assertStringStartsWith('STL-', $settlement->settlement_number);
    }

    public function test_net_amount_calculated_correctly(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['total_amount' => 1000.00, 'fee_amount' => 25.50]),
            $this->user->id
        );

        $this->assertEquals(1000.00, (float) $settlement->total_amount);
        $this->assertEquals(25.50, (float) $settlement->fee_amount);
        $this->assertEquals(974.50, (float) $settlement->net_amount);
    }

    public function test_settlement_no_fee(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['fee_amount' => 0]),
            $this->user->id
        );

        $this->assertEquals(500.00, (float) $settlement->net_amount);
    }

    public function test_je_debits_bank_and_clearing(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['total_amount' => 500.00, 'fee_amount' => 10.00]),
            $this->user->id
        );

        $je = $settlement->journalEntry()->with('lines.account')->first();
        $lines = $je->lines;

        $bankLine = $lines->first(fn ($l) => $l->account_id === $this->bankAccount->id);
        $this->assertNotNull($bankLine);
        $this->assertEquals(490.00, (float) $bankLine->debit);
        $this->assertEquals(0, (float) $bankLine->credit);

        $feeAccount = Account::where('company_id', $this->company->id)->where('code', '6950')->first();
        if ($feeAccount) {
            $feeLine = $lines->first(fn ($l) => $l->account_id === $feeAccount->id);
            $this->assertNotNull($feeLine);
            $this->assertEquals(10.00, (float) $feeLine->debit);
        }

        $clearingAccountId = $this->cardMethod->clearing_account_id;
        $clearingLine = $lines->first(fn ($l) => $l->account_id === $clearingAccountId);
        $this->assertNotNull($clearingLine);
        $this->assertEquals(500.00, (float) $clearingLine->credit);
    }

    public function test_je_is_balanced(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(),
            $this->user->id
        );

        $je = $settlement->journalEntry()->with('lines')->first();
        $totalDebit = $je->lines->sum('debit');
        $totalCredit = $je->lines->sum('credit');

        $this->assertEquals(round($totalDebit, 2), round($totalCredit, 2));
    }

    public function test_je_source_module_is_pos(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(),
            $this->user->id
        );

        $je = $settlement->journalEntry()->first();
        $this->assertEquals('pos', $je->source_module);
    }

    public function test_validation_requires_company_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('company_id is required');

        app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['company_id' => null]),
            $this->user->id
        );
    }

    public function test_validation_requires_payment_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['payment_method_id' => null]),
            $this->user->id
        );
    }

    public function test_validation_requires_bank_account(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['bank_account_id' => null]),
            $this->user->id
        );
    }

    public function test_validation_requires_positive_total(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['total_amount' => 0]),
            $this->user->id
        );
    }

    public function test_validation_rejects_negative_fee(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(['fee_amount' => -5]),
            $this->user->id
        );
    }

    // =============================================
    // CONTROLLER TESTS
    // =============================================

    public function test_index_loads(): void
    {
        $this->get(route('pos.settlements.index'))->assertOk();
    }

    public function test_create_loads(): void
    {
        $this->get(route('pos.settlements.create'))->assertOk();
    }

    public function test_store_creates_settlement(): void
    {
        $this->post(route('pos.settlements.store'), [
            'payment_method_id' => $this->cardMethod->id,
            'bank_account_id' => $this->bankAccount->id,
            'period_start' => now()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'total_amount' => 750.00,
            'fee_amount' => 20.00,
            'reference' => 'BATCH-001',
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_settlements', [
            'company_id' => $this->company->id,
            'status' => 'posted',
            'reference' => 'BATCH-001',
        ]);
    }

    public function test_store_with_zero_fee(): void
    {
        $this->post(route('pos.settlements.store'), [
            'payment_method_id' => $this->cardMethod->id,
            'bank_account_id' => $this->bankAccount->id,
            'period_start' => now()->subDays(3)->toDateString(),
            'period_end' => now()->toDateString(),
            'total_amount' => 200.00,
            'fee_amount' => 0,
        ])->assertRedirect();

        $settlement = PosSettlement::where('company_id', $this->company->id)->first();
        $this->assertEquals(0, (float) $settlement->fee_amount);
        $this->assertEquals(200.00, (float) $settlement->net_amount);
    }

    public function test_show_loads(): void
    {
        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(),
            $this->user->id
        );

        $this->get(route('pos.settlements.show', $settlement->id))->assertOk();
    }

    public function test_company_isolation(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        FeatureManagement::enable($otherCompany->id, 'pos');

        $settlement = app(\App\Services\POS\PosSettlementService::class)->settle(
            $this->baseSettlementData(),
            $this->user->id
        );

        session(['current_company_id' => $otherCompany->id]);

        $this->get(route('pos.settlements.show', $settlement->id))->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\PosPaymentMethod;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTerminalTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'POS Test Co',
            'company_code' => 'PTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        FeatureManagement::enable($this->company->id, 'pos');
    }

    public function test_pos_feature_is_enabled(): void
    {
        $this->assertTrue(FeatureManagement::isEnabled($this->company->id, 'pos'));
    }

    // =============================================
    // TERMINAL CRUD
    // =============================================

    public function test_terminals_index_loads(): void
    {
        $this->get(route('pos.terminals.index'))->assertOk();
    }

    public function test_create_terminal(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Store',
            'code' => 'MS',
            'is_active' => true,
        ]);

        $this->post(route('pos.terminals.store'), [
            'name' => 'Front Counter',
            'identifier' => 'T1',
            'branch_id' => $branch->id,
            'cashier_pin_timeout_minutes' => 30,
        ])->assertRedirect();

        $terminal = PosTerminal::where('company_id', $this->company->id)->first();
        $this->assertNotNull($terminal);
        $this->assertEquals('Front Counter', $terminal->name);
        $this->assertEquals('T1', $terminal->identifier);
        $this->assertEquals($branch->id, $terminal->branch_id);
        $this->assertEquals(30, $terminal->cashier_pin_timeout_minutes);
        $this->assertTrue($terminal->is_active);
    }

    public function test_terminal_identifier_must_be_unique_per_company(): void
    {
        PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Terminal 1',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        $this->post(route('pos.terminals.store'), [
            'name' => 'Terminal 1 Duplicate',
            'identifier' => 'T1',
        ])->assertSessionHasErrors('identifier');
    }

    public function test_update_terminal(): void
    {
        $terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        $this->patch(route('pos.terminals.update', $terminal), [
            'name' => 'New Name',
            'identifier' => 'T1',
            'cashier_pin_timeout_minutes' => 60,
        ])->assertRedirect();

        $terminal->refresh();
        $this->assertEquals('New Name', $terminal->name);
        $this->assertEquals(60, $terminal->cashier_pin_timeout_minutes);
    }

    public function test_toggle_terminal(): void
    {
        $terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Terminal',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        $this->patch(route('pos.terminals.toggle', $terminal))->assertRedirect();
        $terminal->refresh();
        $this->assertFalse($terminal->is_active);

        $this->patch(route('pos.terminals.toggle', $terminal))->assertRedirect();
        $terminal->refresh();
        $this->assertTrue($terminal->is_active);
    }

    public function test_terminal_validates_required_fields(): void
    {
        $this->post(route('pos.terminals.store'), [])
            ->assertSessionHasErrors(['name', 'identifier']);
    }

    // =============================================
    // PAYMENT METHOD CRUD
    // =============================================

    public function test_payment_methods_index_loads(): void
    {
        $this->get(route('pos.payment-methods.index'))->assertOk();
    }

    public function test_create_payment_method(): void
    {
        $clearingAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1060',
            'name' => 'Cash-in-Drawer',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->post(route('pos.payment-methods.store'), [
            'name' => 'Cash',
            'type' => 'cash',
            'clearing_account_id' => $clearingAccount->id,
            'requires_reference' => false,
        ])->assertRedirect();

        $method = PosPaymentMethod::where('company_id', $this->company->id)->first();
        $this->assertNotNull($method);
        $this->assertEquals('Cash', $method->name);
        $this->assertEquals('cash', $method->type);
        $this->assertEquals($clearingAccount->id, $method->clearing_account_id);
        $this->assertFalse($method->requires_reference);
        $this->assertTrue($method->is_active);
    }

    public function test_payment_method_requires_reference(): void
    {
        $clearingAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1070',
            'name' => 'Card Clearing',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->post(route('pos.payment-methods.store'), [
            'name' => 'Visa',
            'type' => 'card',
            'clearing_account_id' => $clearingAccount->id,
            'requires_reference' => true,
        ])->assertRedirect();

        $method = PosPaymentMethod::where('company_id', $this->company->id)->first();
        $this->assertTrue($method->requires_reference);
    }

    public function test_payment_method_name_unique_per_company(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'code' => '1060',
            'name' => 'Cash-in-Drawer',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'clearing_account_id' => $account->id,
            'is_active' => true,
        ]);

        $this->post(route('pos.payment-methods.store'), [
            'name' => 'Cash',
            'type' => 'cash',
        ])->assertSessionHasErrors('name');
    }

    public function test_toggle_payment_method(): void
    {
        $method = PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'is_active' => true,
        ]);

        $this->patch(route('pos.payment-methods.toggle', $method))->assertRedirect();
        $method->refresh();
        $this->assertFalse($method->is_active);
    }

    public function test_payment_method_validates_type(): void
    {
        $this->post(route('pos.payment-methods.store'), [
            'name' => 'Invalid',
            'type' => 'invalid_type',
        ])->assertSessionHasErrors('type');
    }

    // =============================================
    // USER CASHIER PIN
    // =============================================

    public function test_user_can_have_cashier_pin(): void
    {
        $user = User::factory()->create([
            'pos_cashier_pin' => '1234',
        ]);

        $this->assertNotNull($user->pos_cashier_pin);
        $this->assertEquals('1234', $user->pos_cashier_pin);
    }
}

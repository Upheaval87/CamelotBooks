<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Currency;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrenciesPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function makeUser(): User
    {
        return User::factory()->create(['is_super_admin' => false]);
    }

    public function test_non_super_admin_is_forbidden_from_currencies_panel(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('superadmin.currencies.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_currencies_panel(): void
    {
        $this->get(route('superadmin.currencies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_renders_seeded_currency_catalog(): void
    {
        $response = $this->actingAs($this->makeSuperAdmin())
            ->get(route('superadmin.currencies.index'));

        $response->assertOk();
        $response->assertSee('MWK');
        $response->assertSee('Malawian Kwacha');
        $response->assertSee('USD');
        $response->assertSee('US Dollar');
    }

    public function test_create_page_renders(): void
    {
        $response = $this->actingAs($this->makeSuperAdmin())
            ->get(route('superadmin.currencies.create'));

        $response->assertOk();
        $response->assertSee('Currency Code');
    }

    public function test_super_admin_can_store_a_currency_and_it_lands_in_audit_log(): void
    {
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)->post(route('superadmin.currencies.store'), [
            'code' => 'XCD',
            'name' => 'East Caribbean Dollar',
            'symbol' => 'EC$',
            'symbol_position' => 'before',
            'is_active' => '1',
            'sort_order' => '50',
        ])->assertRedirect(route('superadmin.currencies.index'));

        $this->assertDatabaseHas('currencies', [
            'code' => 'XCD',
            'name' => 'East Caribbean Dollar',
            'is_active' => true,
            'sort_order' => 50,
        ]);

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'user_id' => $super->id,
            'action' => SuperAdminAuditLog::ACTION_CURRENCY_CREATED,
            'target_type' => 'currency',
        ]);
    }

    public function test_duplicate_currency_code_is_rejected(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->post(route('superadmin.currencies.store'), [
                'code' => 'MWK',
                'name' => 'Duplicate',
                'symbol_position' => 'before',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_super_admin_can_update_a_currency(): void
    {
        $super = $this->makeSuperAdmin();
        $currency = Currency::query()->create([
            'code' => 'XAF',
            'name' => 'Central African CFA Franc',
            'symbol' => 'FCFA',
            'symbol_position' => 'before',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($super)
            ->patch(route('superadmin.currencies.update', $currency), [
                'code' => 'XAF',
                'name' => 'Central African CFA Franc (BEAC)',
                'symbol' => 'FCFA',
                'symbol_position' => 'after',
                'is_active' => '1',
                'sort_order' => '2',
            ])
            ->assertRedirect(route('superadmin.currencies.index'));

        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'Central African CFA Franc (BEAC)',
            'symbol_position' => 'after',
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'user_id' => $super->id,
            'action' => SuperAdminAuditLog::ACTION_CURRENCY_UPDATED,
        ]);
    }

    public function test_toggle_disables_and_enables_a_currency(): void
    {
        $super = $this->makeSuperAdmin();
        $currency = Currency::query()->create([
            'code' => 'XOF',
            'name' => 'West African CFA Franc',
            'is_active' => true,
        ]);

        $this->actingAs($super)
            ->patch(route('superadmin.currencies.toggle', $currency))
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'is_active' => false]);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'user_id' => $super->id,
            'action' => SuperAdminAuditLog::ACTION_CURRENCY_TOGGLED,
        ]);

        $this->actingAs($super)
            ->patch(route('superadmin.currencies.toggle', $currency))
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'is_active' => true]);
    }

    public function test_company_create_form_selects_currencies_from_catalog_and_includes_mwk(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get(route('superadmin.companies.create'))
            ->assertOk()
            ->assertSee('MWK - Malawian Kwacha')
            ->assertSee('USD - US Dollar')
            ->assertSee('BWP - Botswana Pula');
    }

    public function test_inactive_currencies_are_hidden_from_the_company_create_form(): void
    {
        Currency::query()->create([
            'code' => 'XXX',
            'name' => 'Hidden Currency',
            'is_active' => false,
        ]);

        $this->actingAs($this->makeSuperAdmin())
            ->get(route('superadmin.companies.create'))
            ->assertOk()
            ->assertDontSee('XXX - Hidden Currency');
    }
}

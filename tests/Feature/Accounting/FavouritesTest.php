<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\User;
use App\Services\FavouritesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavouritesTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->otherUser->companies()->attach($this->company->id, ['role' => 'accountant']);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->otherUser->assignRole('accountant');

        session(['current_company_id' => $this->company->id]);
    }

    private function postFavourite(User $user, string $key = 'customers', string $label = 'Customers', string $icon = 'users'): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->postJson(route('favourites.store'), [
            'page_key' => $key,
            'label' => $label,
            'icon' => $icon,
            'url' => route('accounting.customers.index'),
        ]);
    }

    public function test_store_and_index_round_trip(): void
    {
        $this->postFavourite($this->user)->assertJsonPath('favourite.page_key', 'customers');

        $this->actingAs($this->user)
            ->getJson(route('favourites.index'))
            ->assertOk()
            ->assertJsonCount(1, 'favourites')
            ->assertJsonPath('favourites.0.page_key', 'customers');
    }

    public function test_favourites_are_personal(): void
    {
        $this->postFavourite($this->user);

        // Another user cannot see the first user's favourites.
        $this->actingAs($this->otherUser)
            ->getJson(route('favourites.index'))
            ->assertOk()
            ->assertJsonCount(0, 'favourites');

        // And cannot remove them.
        $this->actingAs($this->otherUser)
            ->deleteJson(route('favourites.destroy', ['pageKey' => 'customers']))
            ->assertOk();

        $this->assertDatabaseHas('user_favourites', ['user_id' => $this->user->id, 'page_key' => 'customers']);
    }

    public function test_add_remove_cycle(): void
    {
        $this->postFavourite($this->user);

        $this->actingAs($this->user)
            ->deleteJson(route('favourites.destroy', ['pageKey' => 'customers']))
            ->assertOk();

        $this->assertDatabaseMissing('user_favourites', ['user_id' => $this->user->id, 'page_key' => 'customers']);
    }

    public function test_add_is_idempotent_and_does_not_duplicate(): void
    {
        $this->postFavourite($this->user);
        $this->postFavourite($this->user);

        $this->assertDatabaseCount('user_favourites', 1);
    }

    public function test_enforces_maximum_favourites(): void
    {
        $service = new FavouritesService();
        for ($i = 0; $i < FavouritesService::MAX_FAVOURITES; $i++) {
            $service->add($this->user, "key-$i", "Label $i", 'star', '/x');
        }

        $this->postFavourite($this->user, 'overflow', 'Overflow', 'star')
            ->assertStatus(422);

        $this->assertDatabaseMissing('user_favourites', ['user_id' => $this->user->id, 'page_key' => 'overflow']);
    }

    public function test_reorder_updates_sort_order(): void
    {
        $service = new FavouritesService();
        $service->add($this->user, 'first', 'First', 'star', '/1');
        $service->add($this->user, 'second', 'Second', 'star', '/2');
        $service->add($this->user, 'third', 'Third', 'star', '/3');

        $this->actingAs($this->user)
            ->patchJson(route('favourites.reorder'), ['keys' => ['third', 'first', 'second']])
            ->assertOk();

        $orders = \App\Models\UserFavourite::where('user_id', $this->user->id)
            ->orderBy('sort_order')->pluck('page_key')->all();

        $this->assertSame(['third', 'first', 'second'], $orders);
    }

    public function test_pin_preference_persists(): void
    {
        $this->actingAs($this->user)
            ->patchJson(route('favourites.preferences'), ['sidebar_pinned' => true])
            ->assertOk();

        $this->assertDatabaseHas('user_preferences', ['user_id' => $this->user->id, 'sidebar_pinned' => 1]);

        $this->actingAs($this->user)
            ->getJson(route('favourites.index'))
            ->assertJsonPath('pinned', true);
    }

    public function test_pages_endpoint_exposes_registry(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('favourites.pages'))
            ->assertOk()
            ->assertJsonStructure(['pages' => [['page_key', 'label', 'icon', 'url']]])
            ->assertJsonPath('pages.0.page_key', 'dashboard');

        // My Tasks is always pinned and must not be offered in the picker.
        $this->actingAs($this->user)
            ->getJson(route('favourites.pages'))
            ->assertJsonMissing(['page_key' => 'my-tasks']);
    }

    public function test_registry_route_names_resolve(): void
    {
        foreach (array_keys(FavouritesService::PAGES) as $routeName) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName),
                "Registry route missing: {$routeName}"
            );
        }

        foreach (array_keys(FavouritesService::RECORD_PAGES) as $routeName) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName),
                "Record route missing: {$routeName}"
            );
        }
    }

    public function test_meta_for_record_builds_stable_keys(): void
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('accounting.banking.cheques.show');
        $request = \Illuminate\Http\Request::create('/accounting/banking/cheques/7', 'GET', ['cheque' => 7]);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $meta = FavouritesService::metaForRecord('accounting.banking.cheques.show', 'Cheque #000007');

        $this->assertSame('cheque:7', $meta['key']);
        $this->assertSame('Cheque #000007', $meta['label']);
        $this->assertSame('credit-card', $meta['icon']);
        $this->assertSame('Cheque', $meta['eyebrow']);
    }

    public function test_meta_for_unknown_route_is_null(): void
    {
        $this->assertNull(FavouritesService::metaForRoute('not.a.route'));
        $this->assertNull(FavouritesService::metaForRecord('not.a.route', 'x'));
    }

    public function test_show_page_renders_record_star(): void
    {
        $customer = \App\Models\Customer::create(['company_id' => $this->company->id, 'name' => 'Widget Co']);

        $this->actingAs($this->user)
            ->get(route('accounting.customers.show', $customer->id))
            ->assertOk()
            ->assertSee('favourite-toggle')
            ->assertSee('Customer Detail')
            ->assertSee('Widget Co')
            ->assertSee('Customer', false);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson(route('favourites.index'))->assertUnauthorized();
    }
}

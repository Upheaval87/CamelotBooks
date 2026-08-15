<?php

namespace Tests\Feature\Preference;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FontScalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->post('/preferences/font-scale', ['font_scale' => 1.15]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_update_font_scale(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/preferences/font-scale', ['font_scale' => 1.15]);

        $response->assertOk()
            ->assertJson(['status' => 'ok', 'font_scale' => 1.15]);

        $this->assertSame(1.15, $this->user->fresh()->font_scale);
    }

    public function test_integer_one_matches_the_default_step(): void
    {
        $this->user->update(['font_scale' => 1.15]);

        $response = $this->actingAs($this->user)
            ->postJson('/preferences/font-scale', ['font_scale' => 1]);

        $response->assertOk()
            ->assertJson(['status' => 'ok', 'font_scale' => 1]);

        $this->assertSame(1.0, $this->user->fresh()->font_scale);
    }

    public function test_invalid_scale_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/preferences/font-scale', ['font_scale' => 1.4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['font_scale']);

        $this->assertSame(1.0, $this->user->fresh()->font_scale);
    }

    public function test_layout_applies_stored_font_scale_on_html_element(): void
    {
        $this->user->update(['font_scale' => 1.15]);

        $response = $this->actingAs($this->user)->get(route('panel.dashboard'));

        $response->assertOk();
        $response->assertSee('--font-scale: 1.15', false);
    }

    public function test_layout_defaults_to_normal_scale(): void
    {
        $response = $this->actingAs($this->user)->get(route('panel.dashboard'));

        $response->assertOk();
        $response->assertSee('--font-scale: 1', false);
    }
}

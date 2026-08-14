<?php

namespace Tests\Feature\Preference;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextSizePreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->post('/preferences/text-size', ['size' => 'lg']);

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_update_text_size(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/preferences/text-size', ['size' => 'lg']);

        $response->assertOk()
            ->assertJson(['ok' => true, 'size' => 'lg']);

        $this->assertSame('lg', $this->user->fresh()->text_size);
    }

    public function test_switch_back_to_default(): void
    {
        $this->user->update(['text_size' => 'lg']);

        $this->actingAs($this->user)
            ->postJson('/preferences/text-size', ['size' => 'md'])
            ->assertOk();

        $this->assertSame('md', $this->user->fresh()->text_size);
    }

    public function test_invalid_size_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/preferences/text-size', ['size' => 'xl'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertSame('md', $this->user->fresh()->text_size);
    }

    public function test_layout_applies_stored_text_scale_on_html_element(): void
    {
        $this->user->update(['text_size' => 'lg']);

        $response = $this->actingAs($this->user)->get(route('panel.dashboard'));

        $response->assertOk();
        $response->assertSee('--text-scale: 1.15', false);
    }

    public function test_layout_defaults_to_medium_scale(): void
    {
        $response = $this->actingAs($this->user)->get(route('panel.dashboard'));

        $response->assertOk();
        $response->assertSee('--text-scale: 1', false);
    }
}

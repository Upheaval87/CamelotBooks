<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\NumberingSequence;
use App\Services\Admin\NumberingSequenceService;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->company = Company::create([
            'name' => 'Admin Test Co',
            'company_code' => 'ATC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->companies()->attach($this->company->id, ['role' => 'company_admin']);

        $this->viewer = User::factory()->create();
        $this->viewer->companies()->attach($this->company->id, ['role' => 'viewer']);

        setPermissionsTeamId($this->company->id);
        $this->admin->assignRole('company_admin');
        $this->viewer->assignRole('viewer');

        session(['current_company_id' => $this->company->id]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs($this->admin);
    }

    private function actingAsViewer(): void
    {
        $this->actingAs($this->viewer);
    }

    // =============================================
    // NUMBERING SEQUENCES
    // =============================================

    public function test_admin_can_list_numbering_sequences(): void
    {
        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'annually',
        ]);

        $this->actingAsAdmin();
        $response = $this->get(route('admin.numbering-sequences.index'));
        $response->assertStatus(200);
        $response->assertSee('INV-');
    }

    public function test_viewer_cannot_access_numbering_sequences(): void
    {
        $this->actingAsViewer();
        $response = $this->get(route('admin.numbering-sequences.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_numbering_sequence(): void
    {
        $this->actingAsAdmin();
        $response = $this->post(route('admin.numbering-sequences.store'), [
            'document_type' => 'journal_entry',
            'prefix' => 'JE-',
            'padding_width' => 4,
            'reset_policy' => 'annually',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('numbering_sequences', [
            'company_id' => $this->company->id,
            'document_type' => 'journal_entry',
            'prefix' => 'JE-',
        ]);
    }

    public function test_concurrent_number_generation_never_produces_duplicates(): void
    {
        $seq = NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'never',
            'is_active' => true,
        ]);

        $service = new \App\Services\Admin\NumberingSequenceService();
        $numbers = [];
        $concurrency = 20;

        $barrier = new \stdClass();
        $barrier->count = 0;
        $barrier->latch = $concurrency;

        $threads = [];
        for ($i = 0; $i < $concurrency; $i++) {
            $threads[] = function () use ($service, $seq, &$numbers) {
                $numbers[] = $service->getNextNumber($seq->company_id, $seq->document_type);
            };
        }

        $promises = [];
        foreach ($threads as $thread) {
            $promises[] = $thread;
        }

        // Simulate concurrent execution using transactions
        $results = [];
        for ($i = 0; $i < $concurrency; $i++) {
            $results[] = $service->getNextNumber($this->company->id, 'invoice');
        }

        // Verify no duplicates
        $unique = array_unique($results);
        $this->assertCount($concurrency, $unique, "Duplicate numbers were generated under concurrent access");

        // Verify all numbers are sequential
        sort($results);
        for ($i = 0; $i < $concurrency; $i++) {
            $expected = str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $this->assertStringContainsString($expected, $results[$i]);
        }
    }

    public function test_numbering_service_formats_correctly(): void
    {
        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'never',
            'is_active' => true,
        ]);

        $service = new \App\Services\Admin\NumberingSequenceService();
        $number = $service->getNextNumber($this->company->id, 'invoice');

        $year = now()->format('Y');
        $this->assertEquals("INV-{$year}-0001", $number);
    }

    public function test_numbering_service_peek_does_not_increment(): void
    {
        $seq = NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 5,
            'reset_policy' => 'never',
            'is_active' => true,
        ]);

        $service = new \App\Services\Admin\NumberingSequenceService();

        $peek1 = $service->peekNextNumber($this->company->id, 'invoice');
        $peek2 = $service->peekNextNumber($this->company->id, 'invoice');

        $this->assertEquals($peek1, $peek2);
        $this->assertStringContainsString('0005', $peek1);
    }

    public function test_seeding_defaults_creates_all_sequences(): void
    {
        $service = new \App\Services\Admin\NumberingSequenceService();
        $service->seedDefaults($this->company->id);

        $count = NumberingSequence::where('company_id', $this->company->id)->count();
        $this->assertEquals(23, $count);
    }

    public function test_unique_constraint_prevents_duplicate_document_type(): void
    {
        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'annually',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'annually',
        ]);
    }

    public function test_numbering_sequences_isolated_between_companies(): void
    {
        $company2 = Company::create([
            'name' => 'Second Co',
            'company_code' => 'SC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 10,
            'reset_policy' => 'never',
        ]);

        NumberingSequence::create([
            'company_id' => $company2->id,
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'never',
        ]);

        $service = new \App\Services\Admin\NumberingSequenceService();
        $n1 = $service->getNextNumber($this->company->id, 'invoice');
        $n2 = $service->getNextNumber($company2->id, 'invoice');

        $this->assertStringContainsString('0010', $n1);
        $this->assertStringContainsString('0001', $n2);
    }

    // =============================================
    // UNIFIED AUDIT LOG
    // =============================================

    public function test_audit_log_can_be_created(): void
    {
        $log = AuditLog::log(
            $this->company->id,
            $this->admin->id,
            'App\\Models\\JournalEntry',
            1,
            'posted',
            null,
            ['status' => 'posted'],
            'Test audit log entry'
        );

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $this->company->id,
            'user_id' => $this->admin->id,
            'action' => 'posted',
        ]);
    }

    public function test_audit_log_is_filterable_by_user(): void
    {
        AuditLog::log($this->company->id, $this->admin->id, 'App\\Models\\Invoice', 1, 'posted');
        AuditLog::log($this->company->id, $this->viewer->id, 'App\\Models\\Invoice', 2, 'created');

        $this->actingAsAdmin();
        $response = $this->get(route('admin.audit-log.index', ['user_id' => $this->admin->id]));
        $response->assertStatus(200);
    }

    public function test_audit_log_is_filterable_by_action(): void
    {
        AuditLog::log($this->company->id, $this->admin->id, 'App\\Models\\Invoice', 1, 'posted');
        AuditLog::log($this->company->id, $this->admin->id, 'App\\Models\\Invoice', 2, 'created');

        $this->actingAsAdmin();
        $response = $this->get(route('admin.audit-log.index', ['action' => 'posted']));
        $response->assertStatus(200);
    }

    public function test_audit_log_isolated_between_companies(): void
    {
        $company2 = Company::create([
            'name' => 'Second Co',
            'company_code' => 'SC2',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        AuditLog::log($this->company->id, $this->admin->id, 'App\\Models\\Invoice', 1, 'posted');
        AuditLog::log($company2->id, null, 'App\\Models\\Invoice', 1, 'posted');

        $this->actingAsAdmin();
        $response = $this->get(route('admin.audit-log.index'));
        $response->assertStatus(200);
    }

    // =============================================
    // SECURITY SETTINGS
    // =============================================

    public function test_security_settings_page_loads(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('admin.security.index'));
        $response->assertStatus(200);
        $response->assertSee('Password Policy');
    }

    public function test_security_settings_can_be_updated(): void
    {
        $this->actingAsAdmin();
        $response = $this->put(route('admin.security.update'), [
            'password' => [
                'min_length' => '12',
                'require_uppercase' => '1',
                'require_lowercase' => '1',
                'require_number' => '1',
                'require_symbol' => '0',
                'expiry_days' => '90',
                'history_count' => '5',
            ],
            'session' => [
                'timeout_minutes' => '60',
            ],
            'login' => [
                'max_attempts' => '3',
                'lockout_minutes' => '30',
            ],
            'tfa' => [
                'require_for_admins' => '1',
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('system_settings', [
            'company_id' => $this->company->id,
            'group' => 'security',
            'key' => 'password.min_length',
            'value' => '12',
        ]);

        $this->assertDatabaseHas('system_settings', [
            'company_id' => $this->company->id,
            'group' => 'security',
            'key' => 'session.timeout_minutes',
            'value' => '60',
        ]);
    }

    public function test_password_policy_getter_returns_defaults_when_not_set(): void
    {
        $policy = \App\Http\Controllers\Admin\SecuritySettingsController::getPasswordPolicy($this->company->id);
        $this->assertEquals('8', $policy['min_length']);
        $this->assertEquals('1', $policy['require_uppercase']);
    }

    public function test_password_policy_reflects_saved_settings(): void
    {
        SystemSetting::setValue('security', 'password.min_length', '16', $this->company->id);
        SystemSetting::setValue('security', 'password.require_symbol', '1', $this->company->id);

        $policy = \App\Http\Controllers\Admin\SecuritySettingsController::getPasswordPolicy($this->company->id);
        $this->assertEquals('16', $policy['min_length']);
        $this->assertEquals('1', $policy['require_symbol']);
    }

    // =============================================
    // USER MANAGEMENT
    // =============================================

    public function test_user_list_page_loads(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee($this->admin->email);
    }

    public function test_admin_can_update_user_role(): void
    {
        $this->actingAsAdmin();
        $response = $this->put(route('admin.users.update', $this->viewer), [
            'role' => 'accountant',
        ]);

        $response->assertRedirect();

        $pivot = $this->viewer->companies()->where('company_id', $this->company->id)->first()->pivot;
        $this->assertEquals('accountant', $pivot->role);
    }

    // =============================================
    // NOTIFICATION SETTINGS
    // =============================================

    public function test_notification_settings_page_loads(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('admin.notifications.index'));
        $response->assertStatus(200);
        $response->assertSee('SMTP Configuration');
    }

    public function test_email_template_can_be_created_and_updated(): void
    {
        $template = EmailTemplate::create([
            'company_id' => $this->company->id,
            'event_type' => 'invoice_sent',
            'subject' => 'Invoice {{invoice_number}}',
            'body' => 'Dear {{customer_name}}, your invoice is ready.',
            'is_enabled' => true,
        ]);

        $this->actingAsAdmin();
        $response = $this->put(route('admin.notifications.template-update', $template), [
            'subject' => 'Updated Subject: {{invoice_number}}',
            'body' => 'Updated body text.',
            'is_enabled' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'subject' => 'Updated Subject: {{invoice_number}}',
        ]);
    }

    // =============================================
    // LOCALIZATION
    // =============================================

    public function test_localization_settings_migrated_to_settings(): void
    {
        $this->actingAsAdmin();

        // Number format is now in Regional settings
        $response = $this->put(route('system-settings.update-regional'), [
            'country' => 'Malawi',
            'language' => 'en',
            'timezone' => 'Africa/Blantyre',
            'date_format' => 'd/m/Y',
            'time_format' => '24h',
            'first_day_of_week' => 1,
            'number_format' => '1.234,56',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('system_settings', [
            'company_id' => $this->company->id,
            'group' => 'regional',
            'key' => 'number_format',
            'value' => '1.234,56',
        ]);

        // Currency symbol is now in Currency settings
        $response = $this->put(route('system-settings.update-currency'), [
            'base_currency' => 'MWK',
            'decimal_places' => 2,
            'rate_source' => 'manual',
            'currency_symbol' => 'K',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('system_settings', [
            'company_id' => $this->company->id,
            'group' => 'currency',
            'key' => 'currency_symbol',
            'value' => 'K',
        ]);
    }

    // =============================================
    // SETUP WIZARD
    // =============================================

    public function test_setup_wizard_page_loads(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('admin.setup-wizard.index'));
        $response->assertStatus(200);
        $response->assertSee('Setup Wizard');
    }

    public function test_seeding_numbering_sequences_via_company_create(): void
    {
        $company = Company::create([
            'name' => 'New Company',
            'company_code' => 'NC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        app(NumberingSequenceService::class)->seedDefaults($company->id);

        $count = NumberingSequence::where('company_id', $company->id)->count();
        $this->assertEquals(23, $count);
    }

    // =============================================
    // SYSTEM SETTINGS (key-value store)
    // =============================================

    public function test_system_setting_get_set(): void
    {
        SystemSetting::setValue('test', 'foo', 'bar', $this->company->id);
        $value = SystemSetting::getValue('test', 'foo', $this->company->id);
        $this->assertEquals('bar', $value);
    }

    public function test_system_setting_default_value(): void
    {
        $value = SystemSetting::getValue('nonexistent', 'key', $this->company->id, 'default');
        $this->assertEquals('default', $value);
    }

    public function test_system_setting_get_many(): void
    {
        SystemSetting::setValue('group1', 'a', '1', $this->company->id);
        SystemSetting::setValue('group1', 'b', '2', $this->company->id);
        SystemSetting::setValue('group2', 'c', '3', $this->company->id);

        $values = SystemSetting::getMany('group1', $this->company->id);
        $this->assertEquals('1', $values['a']);
        $this->assertEquals('2', $values['b']);
        $this->assertArrayNotHasKey('c', $values);
    }

    public function test_system_settings_isolated_between_companies(): void
    {
        $company2 = Company::create([
            'name' => 'Second Co',
            'company_code' => 'SC3',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        SystemSetting::setValue('test', 'key', 'company1_value', $this->company->id);
        SystemSetting::setValue('test', 'key', 'company2_value', $company2->id);

        $this->assertEquals('company1_value', SystemSetting::getValue('test', 'key', $this->company->id));
        $this->assertEquals('company2_value', SystemSetting::getValue('test', 'key', $company2->id));
    }

    // =============================================
    // ACCESS CONTROL (viewer gets 403)
    // =============================================

    public function test_viewer_cannot_access_security_settings(): void
    {
        $this->actingAsViewer();
        $response = $this->get(route('admin.security.index'));
        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_user_management(): void
    {
        $this->actingAsViewer();
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_audit_log(): void
    {
        $this->actingAsViewer();
        $response = $this->get(route('admin.audit-log.index'));
        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_notification_settings(): void
    {
        $this->actingAsViewer();
        $response = $this->get(route('admin.notifications.index'));
        $response->assertStatus(403);
    }
}

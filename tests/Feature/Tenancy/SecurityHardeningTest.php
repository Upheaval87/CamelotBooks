<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\CompanySupportSession;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\Admin\DatabaseBackupService;
use App\Services\Verification\CompanyMigrationVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 6 security & hardening:
 *  - db_password encrypted at rest + never serialized/rendered;
 *  - forged session company (never accessed) rejected by the tenant middleware;
 *  - login attempts logged (success/failure/deactivated/rate-limited);
 *  - company switch rate-limited;
 *  - super-admin support sessions tracked with start/end + duration, closed on
 *    switch and logout;
 *  - per-tenant database backup -> restore round trip reconciled with the Phase 2
 *    migration verifier, and per-tenant audit_logs present in a fresh tenant.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const TENANT_MIGRATIONS = __DIR__ . '/../../../database/migrations/tenant';

    private array $createdDbs = [];

    private array $createdDumpFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdDbs as $name) {
            try {
                DB::connection('provisioning')->statement("DROP DATABASE IF EXISTS `{$name}`");
            } catch (\Throwable) {
            }
        }

        $this->createdDbs = [];

        foreach ($this->createdDumpFiles as $path) {
            try {
                @unlink($path);
            } catch (\Throwable) {
            }
        }

        $this->createdDumpFiles = [];

        parent::tearDown();
    }

    private function makeCompany(string $code = 'ACME', bool $provisioned = true): Company
    {
        return Company::create([
            'company_code' => $code,
            'name' => $code . ' Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => $provisioned ? Company::STATUS_ACTIVE : Company::STATUS_PENDING,
            'db_name' => $provisioned ? 'acct_' . strtolower($code) . '_00000001' : null,
            'provisioned_at' => $provisioned ? now() : null,
        ]);
    }

    private function assign(User $user, Company $company, string $role = 'company_admin', bool $active = true): UserCompanyAssignment
    {
        return UserCompanyAssignment::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    // =============================================
    // db_password AT-REST ENCRYPTION
    // =============================================

    public function test_db_password_is_encrypted_at_rest_and_never_serialized(): void
    {
        $company = Company::create([
            'name' => 'Secret Co',
            'company_code' => 'SEC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'db_password' => 'S3cr3t!TenantPass',
        ]);

        $raw = DB::table('companies')->where('id', $company->id)->value('db_password');

        $this->assertNotSame('S3cr3t!TenantPass', $raw);
        $this->assertTrue(str_starts_with((string) $raw, 'eyJ'), 'raw value should be the base64 JSON encryption envelope');

        $fresh = Company::find($company->id);
        $this->assertSame('S3cr3t!TenantPass', $fresh->db_password);

        $this->assertArrayNotHasKey('db_password', $fresh->toArray());
        $this->assertStringNotContainsString('S3cr3t!TenantPass', json_encode($fresh));
    }

    public function test_superadmin_company_page_never_renders_db_credentials(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);
        $company = $this->makeCompany();
        $company->update(['db_password' => 'Sup3r!SecretPass']);

        $this->actingAs($super)
            ->get(route('superadmin.companies.show', $company))
            ->assertOk()
            ->assertDontSee('Sup3r!SecretPass');
    }

    // =============================================
    // FORGE-PROOF COMPANY CONTEXT
    // =============================================

    public function test_forged_session_company_the_user_never_had_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();

        $this->actingAs($user)
            ->withSession(['current_company_id' => $company->id])
            ->get('/dashboard')
            ->assertRedirect(route('companies.index', absolute: false));

        $this->assertNull(session('current_company_id'));
    }

    public function test_select_of_a_real_company_without_an_assignment_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $this->assign(User::factory()->create(), $company); // someone else belongs

        $this->actingAs($user)
            ->post(route('companies.select', $company->id))
            ->assertNotFound();

        $this->assertNull(session('current_company_id'));
    }

    // =============================================
    // LOGIN ATTEMPT LOGGING
    // =============================================

    public function test_failed_login_attempt_is_logged(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('login_attempt_logs', [
            'email' => $user->email,
            'success' => false,
            'failure_reason' => 'invalid_credentials',
            'user_id' => null,
        ]);
    }

    public function test_successful_login_is_logged_with_user_id(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('login_attempt_logs', [
            'email' => $user->email,
            'success' => true,
            'failure_reason' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_deactivated_account_login_is_logged(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('login_attempt_logs', [
            'email' => $user->email,
            'success' => false,
            'failure_reason' => 'deactivated',
            'user_id' => $user->id,
        ]);
    }

    // =============================================
    // COMPANY SWITCH RATE LIMITING
    // =============================================

    public function test_company_switch_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $this->assign($user, $company);

        $this->actingAs($user);

        $response = null;
        for ($i = 0; $i < 20; $i++) {
            $response = $this->post(route('companies.select', $company->id));
        }

        $response->assertRedirect();

        $this->post(route('companies.select', $company->id))->assertStatus(429);
    }

    // =============================================
    // SUPER-ADMIN SUPPORT SESSIONS
    // =============================================

    public function test_support_session_tracks_entry_duration_and_closes_on_switch_and_logout(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);
        $companyA = $this->makeCompany('ACME');
        $companyB = $this->makeCompany('BETA');
        $this->assign($super, $companyA);
        $this->assign($super, $companyB);

        $this->actingAs($super)->post(route('companies.select', $companyA->id))
            ->assertRedirect(route('dashboard', absolute: false));

        $sessionA = CompanySupportSession::where('user_id', $super->id)
            ->where('company_id', $companyA->id)
            ->first();

        $this->assertNotNull($sessionA);
        $this->assertNull($sessionA->ended_at);

        // Switching to B closes A's session (with duration) and opens one for B.
        $this->actingAs($super)->post(route('companies.select', $companyB->id))
            ->assertRedirect(route('dashboard', absolute: false));

        $sessionA->refresh();
        $this->assertNotNull($sessionA->ended_at);
        $this->assertSame(CompanySupportSession::ENDED_CONTEXT_CHANGED, $sessionA->ended_reason);
        $this->assertNotNull($sessionA->duration);

        $sessionB = CompanySupportSession::where('user_id', $super->id)
            ->where('company_id', $companyB->id)
            ->whereNull('ended_at')
            ->first();

        $this->assertNotNull($sessionB);

        // Logout closes the open session.
        $this->post('/logout');

        $sessionB->refresh();
        $this->assertNotNull($sessionB->ended_at);
        $this->assertSame(CompanySupportSession::ENDED_LOGOUT, $sessionB->ended_reason);
    }

    // =============================================
    // PER-TENANT BACKUP -> RESTORE (real MySQL)
    // =============================================

    public function test_backup_restore_round_trip_reconciles_and_tenant_has_audit_logs(): void
    {
        $this->ensureMysqlAvailable();

        $binaries = $this->resolveBackupBinaries();
        if ($binaries === null) {
            $this->markTestSkipped('mysqldump/mysql binaries not found on this machine.');
        }

        // Real tenant routing (no sqlite override) so the tenant connection is a
        // genuine MySQL connection the dump reads from.
        Config::set('tenancy.routing.connection_override', null);
        Config::set('database.backup.binary', $binaries['dump']);
        Config::set('database.backup.restore_binary', $binaries['mysql']);

        $sourceConnection = $this->createSourceDatabase();
        $this->seedSource($sourceConnection);

        // The tenant schema must carry its own row-level audit table.
        $this->assertTrue(Schema::connection($sourceConnection)->hasTable('audit_logs'));

        $company = Company::create([
            'name' => 'Backup Test Co',
            'company_code' => 'BKPC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_ACTIVE,
            'db_name' => DB::connection($sourceConnection)->getDatabaseName(),
            'provisioned_at' => now(),
        ]);

        $service = app(DatabaseBackupService::class);

        $log = $service->backup($company, null, 'scheduler');

        $this->assertSame('success', $log->status, $log->filename . ' :: ' . ($log->error_message ?? 'no error'));
        $fullPath = storage_path('app/backups/' . $log->filename);
        $this->createdDumpFiles[] = $fullPath;
        $this->assertFileExists($fullPath);
        $this->assertGreaterThan(0, $log->file_size_bytes);

        // The BackupLog row landed in the TENANT database.
        $this->assertSame(1, $this->countIn($service->backupConfigFor($company), 'backup_logs', $company->id));

        // Restore the dump into a fresh empty database.
        $restoreName = 'backup_restore_' . substr(bin2hex(random_bytes(4)), 0, 8);
        DB::connection('provisioning')->statement("CREATE DATABASE `{$restoreName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->createdDbs[] = $restoreName;

        $restoreConfig = $service->backupConfigFor($company);
        $restoreConfig['database'] = $restoreName;

        [$exitCode, $output] = $service->restoreFromFile($restoreConfig, $fullPath);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        // Reconcile the restored DB against the source using the Phase 2 verifier.
        $restoreConnection = 'backup_restored';
        Config::set("database.connections.{$restoreConnection}", $restoreConfig);
        DB::purge($restoreConnection);

        $verification = app(CompanyMigrationVerifier::class)->verify($company, $restoreConnection, $sourceConnection);

        $this->assertTrue($verification['passed'], implode(PHP_EOL, array_map(
            static fn (array $c): string => $c['name'] . ' [' . $c['status'] . '] expected=' . json_encode($c['expected']) . ' actual=' . json_encode($c['actual']),
            array_filter($verification['checks'], static fn (array $c): bool => $c['status'] !== 'passed'),
        )));

        // The resolver is unbound after the standalone backup call.
        $this->assertNull(app(\App\Services\Tenancy\TenantConnectionResolver::class)->boundCompanyId());
    }

    // =============================================
    // Real-MySQL helpers
    // =============================================

    private function ensureMysqlAvailable(): void
    {
        try {
            DB::connection('provisioning')->selectOne('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL provisioning connection unavailable: ' . $e->getMessage());
        }
    }

    private function resolveBackupBinaries(): ?array
    {
        $candidates = [
            ['dump' => 'C:\\xampp\\mysql\\bin\\mysqldump.exe', 'mysql' => 'C:\\xampp\\mysql\\bin\\mysql.exe'],
            ['dump' => 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe', 'mysql' => 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe'],
            ['dump' => 'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysqldump.exe', 'mysql' => 'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe'],
        ];

        foreach ($candidates as $pair) {
            if (is_file($pair['dump']) && is_file($pair['mysql'])) {
                return $pair;
            }
        }

        $dump = env('DB_MYSQLDUMP');
        $mysql = env('DB_MYSQL');

        if ($dump && $mysql && is_file($dump) && is_file($mysql)) {
            return ['dump' => $dump, 'mysql' => $mysql];
        }

        return null;
    }

    private function createSourceDatabase(): string
    {
        $name = 'backup_source_' . substr(bin2hex(random_bytes(4)), 0, 8);
        DB::connection('provisioning')->statement("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->createdDbs[] = $name;

        $connection = 'backup_source';
        Config::set("database.connections.{$connection}", array_merge(
            config('database.connections.provisioning'),
            ['database' => $name],
        ));
        DB::purge($connection);

        $central = DB::getDefaultConnection();

        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => self::TENANT_MIGRATIONS,
                '--realpath' => true,
                '--force' => true,
            ]);
        } finally {
            DB::setDefaultConnection($central);
        }

        if ($exitCode !== 0) {
            throw new \RuntimeException('Scratch source migration failed: ' . \Illuminate\Support\Facades\Artisan::output());
        }

        return $connection;
    }

    private function seedSource(string $connection): void
    {
        $db = DB::connection($connection);
        $now = now();

        $db->statement('SET FOREIGN_KEY_CHECKS = 0');

        $db->table('companies')->insert(['id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $db->table('users')->insert([
            ['id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('branches')->insert([
            'id' => 1, 'company_id' => 1, 'name' => 'Head Office', 'code' => 'HQ', 'address' => null,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('accounts')->insert([
            ['id' => 1, 'company_id' => 1, 'parent_id' => null, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'company_id' => 1, 'parent_id' => null, 'code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'company_id' => 1, 'parent_id' => null, 'code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'sub_type' => 'equity', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'company_id' => 1, 'parent_id' => null, 'code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_income', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('accounting_periods')->insert([
            'id' => 1, 'company_id' => 1, 'label' => 'January 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31',
            'status' => 'open', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('customers')->insert([
            'id' => 1, 'company_id' => 1, 'branch_id' => null, 'name' => 'Test Customer', 'currency' => 'USD',
            'payment_terms' => 'net_30', 'opening_balance' => 0, 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('products')->insert([
            'id' => 1, 'company_id' => 1, 'name' => 'Consulting', 'sku' => 'SV-001', 'type' => 'service',
            'sales_price' => 100.00, 'purchase_price' => null, 'income_account_id' => 4, 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('journal_entries')->insert([
            'id' => 1, 'company_id' => 1, 'branch_id' => null, 'journal_number' => 'JE-001', 'date' => '2026-01-15',
            'status' => 'posted', 'source_module' => 'manual', 'created_by' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('journal_entry_lines')->insert([
            ['id' => 1, 'journal_entry_id' => 1, 'account_id' => 1, 'branch_id' => null, 'cost_center_id' => null, 'debit' => 100.00, 'credit' => 0.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'journal_entry_id' => 1, 'account_id' => 3, 'branch_id' => null, 'cost_center_id' => null, 'debit' => 0.00, 'credit' => 100.00, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('invoices')->insert([
            'id' => 1, 'company_id' => 1, 'branch_id' => null, 'customer_id' => 1, 'invoice_number' => 'INV-001',
            'invoice_date' => '2026-01-15', 'due_date' => '2026-02-14', 'status' => 'draft',
            'amount' => 100.00, 'amount_paid' => 0.00, 'currency' => 'USD', 'created_by' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('invoice_lines')->insert([
            'id' => 1, 'invoice_id' => 1, 'product_id' => 1, 'description' => 'Consulting', 'quantity' => 1.00,
            'unit_price' => 100.00, 'discount' => 0, 'tax_rate' => 0, 'amount' => 100.00, 'tax_amount' => 0,
            'line_total' => 100.00, 'income_account_id' => 4, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('permissions')->insert([
            ['id' => 1, 'name' => 'view accounts', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'create accounts', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('roles')->insert([
            ['id' => 1, 'name' => 'company_admin', 'guard_name' => 'web', 'company_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('role_has_permissions')->insert([
            ['permission_id' => 1, 'role_id' => 1],
            ['permission_id' => 2, 'role_id' => 1],
        ]);

        $db->table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 1, 'company_id' => 1],
        ]);

        Schema::connection($connection)->create('company_user', function ($table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('user_id');
            $table->string('role')->nullable();
            $table->timestamps();
        });

        $db->table('company_user')->insert([
            ['id' => 1, 'company_id' => 1, 'user_id' => 1, 'role' => 'company_admin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'company_id' => 1, 'user_id' => 2, 'role' => 'accountant', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('user_favourites')->insert([
            'id' => 1, 'user_id' => 1, 'page_key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home',
            'url' => '/dashboard', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('user_preferences')->insert([
            'user_id' => 1, 'sidebar_pinned' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function countIn(array $config, string $table, int $companyId): int
    {
        $name = 'backup_check_' . bin2hex(random_bytes(3));
        Config::set("database.connections.{$name}", $config);
        DB::purge($name);

        return (int) DB::connection($name)->table($table)->where('company_id', $companyId)->count();
    }
}

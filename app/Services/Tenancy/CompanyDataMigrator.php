<?php

namespace App\Services\Tenancy;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies an existing company's live data out of the shared database into its
 * freshly provisioned tenant database, preserving primary keys so all
 * intra-company foreign keys keep pointing at the same rows.
 *
 * The copy is a raw cross-database `INSERT ... SELECT` (source and tenant live
 * on the same MySQL server). Original IDs are preserved and `FOREIGN_KEY_CHECKS`
 * is disabled for the duration of the copy.
 *
 * Tables that are intentionally NOT copied:
 *  - BI mart aggregates (the dim_ and fact_ tables plus bi_refresh_log): rebuilt
 *    by `bi:refresh-data-mart` against the tenant connection.
 *  - Central infrastructure (companies/users rows, company_user, modules,
 *    company_modules, cache/jobs/sessions, migrations).
 */
class CompanyDataMigrator
{
    /**
     * Tables scoped directly by `company_id`. Copied with
     * `WHERE company_id = :company`.
     */
    public const DIRECT_TABLES = [
        'account_audit_logs',
        'accounting_periods',
        'accounts',
        'approval_settings',
        'approval_thresholds',
        'assembly_builds',
        'asset_categories',
        'asset_disposals',
        'asset_impairments',
        'asset_revaluations',
        'asset_transfers',
        'assets',
        'attachments',
        'audit_logs',
        'backup_logs',
        'bank_reconciliations',
        'bank_statement_imports',
        'bank_transactions',
        'bi_digest_schedules',
        'bill_of_materials',
        'bills',
        'branches',
        'budgets',
        'cheques',
        'company_allowances',
        'cost_centers',
        'credit_notes',
        'customer_payments',
        'customers',
        'default_account_mappings',
        'depreciation_runs',
        'eis_submissions',
        'eis_terminals',
        'email_templates',
        'employee_payments',
        'employee_salary_structures',
        'employees',
        'exchange_rates',
        'expenses',
        'fiscal_years',
        'goods_received_notes',
        'inventory_adjustments',
        'inventory_cost_layers',
        'inventory_stock',
        'inventory_transfers',
        'invoices',
        'item_categories',
        'item_uom_conversions',
        'journal_entries',
        'landed_cost_vouchers',
        'mobile_money_providers',
        'model_has_permissions',
        'model_has_roles',
        'numbering_sequences',
        'paye_tables',
        'payroll_runs',
        'payslip_deliveries',
        'pension_schemes',
        'pos_cashier_sessions',
        'pos_payment_methods',
        'pos_returns',
        'pos_sales',
        'pos_settlements',
        'pos_terminals',
        'products',
        'purchase_orders',
        'purchase_requisitions',
        'quotations',
        'recurring_bill_templates',
        'recurring_invoice_templates',
        'recurring_journal_templates',
        'request_for_quotations',
        'sales_receipts',
        'settings_backups',
        'stock_counts',
        'system_settings',
        'todo_tasks',
        'units_of_production_usage_entries',
        'vendor_credits',
        'vendor_payments',
        'vendors',
    ];

    /**
     * Child/line tables without a `company_id` column. Each row is scoped via an
     * EXISTS predicate against the company-owned parent chain. `{source}` is
     * replaced with the (backticked) source database name, `s.` aliases the
     * child table, and every `?` binds the company id.
     */
    public const CHAIN_TABLES = [
        'bank_reconciliation_items' => 'EXISTS (SELECT 1 FROM {source}.`bank_reconciliations` p WHERE p.id = s.`reconciliation_id` AND p.`company_id` = ?) OR EXISTS (SELECT 1 FROM {source}.`bank_transactions` p WHERE p.id = s.`bank_transaction_id` AND p.`company_id` = ?) OR EXISTS (SELECT 1 FROM {source}.`bank_statement_lines` p WHERE p.id = s.`bank_statement_line_id` AND EXISTS (SELECT 1 FROM {source}.`bank_statement_imports` i WHERE i.id = p.`import_id` AND i.`company_id` = ?))',
        'bill_lines' => 'EXISTS (SELECT 1 FROM {source}.`bills` p WHERE p.id = s.`bill_id` AND p.`company_id` = ?)',
        'bill_of_material_lines' => 'EXISTS (SELECT 1 FROM {source}.`bill_of_materials` p WHERE p.id = s.`bom_id` AND p.`company_id` = ?)',
        'budget_lines' => 'EXISTS (SELECT 1 FROM {source}.`budgets` p WHERE p.id = s.`budget_id` AND p.`company_id` = ?)',
        'credit_note_allocations' => 'EXISTS (SELECT 1 FROM {source}.`credit_notes` p WHERE p.id = s.`credit_note_id` AND p.`company_id` = ?)',
        'credit_note_lines' => 'EXISTS (SELECT 1 FROM {source}.`credit_notes` p WHERE p.id = s.`credit_note_id` AND p.`company_id` = ?)',
        'customer_payment_allocations' => 'EXISTS (SELECT 1 FROM {source}.`customer_payments` p WHERE p.id = s.`customer_payment_id` AND p.`company_id` = ?)',
        'depreciation_schedule_entries' => 'EXISTS (SELECT 1 FROM {source}.`depreciation_runs` p WHERE p.id = s.`depreciation_run_id` AND p.`company_id` = ?)',
        'employee_salary_items' => 'EXISTS (SELECT 1 FROM {source}.`employee_salary_structures` p WHERE p.id = s.`salary_structure_id` AND p.`company_id` = ?) OR EXISTS (SELECT 1 FROM {source}.`company_allowances` p WHERE p.id = s.`company_allowance_id` AND p.`company_id` = ?)',
        'expense_lines' => 'EXISTS (SELECT 1 FROM {source}.`expenses` p WHERE p.id = s.`expense_id` AND p.`company_id` = ?)',
        'grn_lines' => 'EXISTS (SELECT 1 FROM {source}.`goods_received_notes` p WHERE p.id = s.`goods_received_note_id` AND p.`company_id` = ?)',
        'invoice_lines' => 'EXISTS (SELECT 1 FROM {source}.`invoices` p WHERE p.id = s.`invoice_id` AND p.`company_id` = ?)',
        'journal_entry_lines' => 'EXISTS (SELECT 1 FROM {source}.`journal_entries` p WHERE p.id = s.`journal_entry_id` AND p.`company_id` = ?)',
        'landed_cost_components' => 'EXISTS (SELECT 1 FROM {source}.`landed_cost_vouchers` p WHERE p.id = s.`voucher_id` AND p.`company_id` = ?)',
        'landed_cost_voucher_grns' => 'EXISTS (SELECT 1 FROM {source}.`landed_cost_vouchers` p WHERE p.id = s.`voucher_id` AND p.`company_id` = ?) OR EXISTS (SELECT 1 FROM {source}.`goods_received_notes` p WHERE p.id = s.`grn_id` AND p.`company_id` = ?)',
        'payables' => 'EXISTS (SELECT 1 FROM {source}.`invoices` p WHERE p.id = s.`invoice_id` AND p.`company_id` = ?) OR EXISTS (SELECT 1 FROM {source}.`bills` p WHERE p.id = s.`bill_id` AND p.`company_id` = ?)',
        'paye_table_bands' => 'EXISTS (SELECT 1 FROM {source}.`paye_tables` p WHERE p.id = s.`paye_table_id` AND p.`company_id` = ?)',
        'payroll_run_items' => 'EXISTS (SELECT 1 FROM {source}.`payroll_runs` p WHERE p.id = s.`payroll_run_id` AND p.`company_id` = ?)',
        'pos_payments' => 'EXISTS (SELECT 1 FROM {source}.`pos_sales` p WHERE p.id = s.`pos_sale_id` AND p.`company_id` = ?)',
        'pos_return_lines' => 'EXISTS (SELECT 1 FROM {source}.`pos_returns` p WHERE p.id = s.`pos_return_id` AND p.`company_id` = ?)',
        'pos_sale_lines' => 'EXISTS (SELECT 1 FROM {source}.`pos_sales` p WHERE p.id = s.`pos_sale_id` AND p.`company_id` = ?)',
        'purchase_order_lines' => 'EXISTS (SELECT 1 FROM {source}.`purchase_orders` p WHERE p.id = s.`purchase_order_id` AND p.`company_id` = ?)',
        'purchase_requisition_lines' => 'EXISTS (SELECT 1 FROM {source}.`purchase_requisitions` p WHERE p.id = s.`purchase_requisition_id` AND p.`company_id` = ?)',
        'quotation_lines' => 'EXISTS (SELECT 1 FROM {source}.`quotations` p WHERE p.id = s.`quotation_id` AND p.`company_id` = ?)',
        'recurring_bill_template_lines' => 'EXISTS (SELECT 1 FROM {source}.`recurring_bill_templates` p WHERE p.id = s.`rbt_id` AND p.`company_id` = ?)',
        'recurring_invoice_template_lines' => 'EXISTS (SELECT 1 FROM {source}.`recurring_invoice_templates` p WHERE p.id = s.`rit_id` AND p.`company_id` = ?)',
        'recurring_journal_template_lines' => 'EXISTS (SELECT 1 FROM {source}.`recurring_journal_templates` p WHERE p.id = s.`rjt_id` AND p.`company_id` = ?)',
        'rfq_lines' => 'EXISTS (SELECT 1 FROM {source}.`request_for_quotations` p WHERE p.id = s.`request_for_quotation_id` AND p.`company_id` = ?)',
        'rfq_vendor_quotes' => 'EXISTS (SELECT 1 FROM {source}.`rfq_lines` l WHERE l.id = s.`rfq_line_id` AND EXISTS (SELECT 1 FROM {source}.`request_for_quotations` q WHERE q.id = l.`request_for_quotation_id` AND q.`company_id` = ?)) OR EXISTS (SELECT 1 FROM {source}.`vendors` p WHERE p.id = s.`vendor_id` AND p.`company_id` = ?)',
        'sales_receipt_lines' => 'EXISTS (SELECT 1 FROM {source}.`sales_receipts` p WHERE p.id = s.`sales_receipt_id` AND p.`company_id` = ?)',
        'sales_receipt_payments' => 'EXISTS (SELECT 1 FROM {source}.`sales_receipts` p WHERE p.id = s.`sales_receipt_id` AND p.`company_id` = ?)',
        'stock_count_lines' => 'EXISTS (SELECT 1 FROM {source}.`stock_counts` p WHERE p.id = s.`stock_count_id` AND p.`company_id` = ?)',
        'vendor_credit_allocations' => 'EXISTS (SELECT 1 FROM {source}.`vendor_credits` p WHERE p.id = s.`vendor_credit_id` AND p.`company_id` = ?)',
        'vendor_credit_lines' => 'EXISTS (SELECT 1 FROM {source}.`vendor_credits` p WHERE p.id = s.`vendor_credit_id` AND p.`company_id` = ?)',
        'vendor_payment_allocations' => 'EXISTS (SELECT 1 FROM {source}.`vendor_payments` p WHERE p.id = s.`vendor_payment_id` AND p.`company_id` = ?)',
    ];

    /**
     * Shared reference tables (permissions/roles/grant mappings) copied in full —
     * every row, regardless of company. Live roles carry a NULL company_id.
     */
    public const ALL_ROWS_TABLES = [
        'permissions',
        'roles',
        'role_has_permissions',
    ];

    /**
     * Personal, user-scoped tables copied for each user who belongs to the
     * company (per the Phase 2 decision: "copy to each tenant").
     */
    public const MEMBERSHIP_TABLES = [
        'user_favourites',
        'user_preferences',
    ];

    /**
     * BI mart aggregates rebuilt by `bi:refresh-data-mart` — never copied.
     */
    public const REBUILT_TABLES = [
        'dim_date',
        'dim_company',
        'dim_branch',
        'dim_cost_center',
        'dim_account',
        'dim_item',
        'dim_customer',
        'dim_vendor',
        'dim_employee',
        'fact_general_ledger',
        'fact_sales',
        'fact_purchases',
        'fact_payroll',
        'fact_inventory_movement',
        'bi_refresh_log',
    ];

    /**
     * Columns that reference users, used to seed stub `users` rows in the tenant
     * (whose users table is intentionally a minimal id + timestamps stub).
     * Queried against the tenant after the copy so no company scoping is needed.
     */
    public const USER_FK_COLUMNS = [
        'account_audit_logs' => ['user_id'],
        'accounting_periods' => ['closed_by'],
        'assembly_builds' => ['created_by'],
        'asset_disposals' => ['created_by'],
        'asset_impairments' => ['created_by'],
        'asset_revaluations' => ['created_by'],
        'asset_transfers' => ['created_by'],
        'assets' => ['created_by'],
        'attachments' => ['uploaded_by'],
        'audit_logs' => ['user_id'],
        'backup_logs' => ['user_id'],
        'bank_reconciliations' => ['completed_by'],
        'bank_statement_imports' => ['imported_by'],
        'bank_transactions' => ['created_by'],
        'bills' => ['approved_by', 'created_by', 'posted_by', 'voided_by'],
        'budgets' => ['approved_by', 'created_by'],
        'cheques' => ['created_by', 'voided_by'],
        'credit_notes' => ['created_by', 'posted_by', 'voided_by'],
        'customer_payments' => ['created_by'],
        'depreciation_runs' => ['created_by', 'posted_by'],
        'employee_payments' => ['created_by'],
        'expenses' => ['created_by', 'posted_by', 'voided_by'],
        'goods_received_notes' => ['created_by'],
        'inventory_adjustments' => ['created_by'],
        'inventory_transfers' => ['created_by'],
        'invoices' => ['created_by', 'posted_by', 'voided_by'],
        'journal_entries' => ['approved_by', 'created_by', 'posted_by', 'rejected_by'],
        'landed_cost_vouchers' => ['created_by'],
        'payroll_runs' => ['created_by'],
        'pos_cashier_sessions' => ['user_id'],
        'pos_returns' => ['created_by', 'posted_by'],
        'pos_settlements' => ['settled_by'],
        'purchase_orders' => ['created_by'],
        'purchase_requisitions' => ['approved_by', 'created_by'],
        'quotations' => ['created_by', 'posted_by', 'voided_by'],
        'recurring_bill_templates' => ['created_by'],
        'recurring_invoice_templates' => ['created_by'],
        'recurring_journal_templates' => ['created_by'],
        'request_for_quotations' => ['created_by'],
        'sales_receipts' => ['created_by', 'posted_by', 'voided_by'],
        'settings_backups' => ['created_by'],
        'stock_counts' => ['created_by'],
        'todo_tasks' => ['user_id'],
        'units_of_production_usage_entries' => ['created_by'],
        'vendor_credits' => ['created_by', 'posted_by', 'voided_by'],
        'vendor_payments' => ['created_by'],
        'user_favourites' => ['user_id'],
        'user_preferences' => ['user_id'],
    ];

    /** @var array<string, array<string, string>> columns used by the migrator itself */
    private array $columnCache = [];

    /**
     * Copy a company's data from the shared database into its tenant database.
     *
     * @return array<string, int> rows copied per table
     *
     * @throws \RuntimeException on schema drift or copy failure
     */
    public function migrate(Company $company, string $tenantConnection, string $sourceConnection = 'mysql'): array
    {
        $sourceDb = DB::connection($sourceConnection)->getDatabaseName();
        $targetDb = DB::connection($tenantConnection)->getDatabaseName();

        if ($sourceDb === $targetDb) {
            throw new \RuntimeException("Source and target database are the same [{$sourceDb}].");
        }

        $this->assertSourceAndTargetOnSameServer($sourceConnection, $tenantConnection);

        $this->assertSchemaParity($company, $sourceConnection, $tenantConnection);
        DB::connection($tenantConnection)->statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            $copied = [];

            $this->insertTenantCompanyRow($company, $tenantConnection);

            foreach (self::ALL_ROWS_TABLES as $table) {
                $copied[$table] = $this->copyRows(
                    $tenantConnection, $sourceConnection, $table,
                    'INSERT INTO `' . $targetDb . '`.`' . $table . '` ({columns}) SELECT {columns} FROM `' . $sourceDb . '`.`' . $table . '`',
                );
            }

            foreach (self::DIRECT_TABLES as $table) {
                $copied[$table] = $this->copyRows(
                    $tenantConnection, $sourceConnection, $table,
                    'INSERT INTO `' . $targetDb . '`.`' . $table . '` ({columns}) SELECT {columns} FROM `' . $sourceDb . '`.`' . $table . '` WHERE `company_id` = ?',
                    [$company->id],
                );
            }

            foreach (array_keys(self::CHAIN_TABLES) as $table) {
                $predicate = str_replace('{source}', '`' . $sourceDb . '`', self::CHAIN_TABLES[$table]);
                $bindings = array_fill(0, substr_count($predicate, '?'), $company->id);

                $copied[$table] = $this->copyRows(
                    $tenantConnection, $sourceConnection, $table,
                    'INSERT INTO `' . $targetDb . '`.`' . $table . '` ({columns}) SELECT s.{columns} FROM `' . $sourceDb . '`.`' . $table . '` s WHERE ' . $predicate,
                    $bindings,
                );
            }

            foreach (self::MEMBERSHIP_TABLES as $table) {
                $copied[$table] = $this->copyRows(
                    $tenantConnection, $sourceConnection, $table,
                    'INSERT INTO `' . $targetDb . '`.`' . $table . '` ({columns}) SELECT {columns} FROM `' . $sourceDb . '`.`' . $table . '` WHERE `user_id` IN (SELECT `user_id` FROM `' . $sourceDb . '`.`company_user` WHERE `company_id` = ?)',
                    [$company->id],
                );
            }

            $this->seedStubUsers($company, $tenantConnection, $sourceConnection, $sourceDb);

            return $copied;
        } finally {
            DB::connection($tenantConnection)->statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * Assert that every manifest table exists in the tenant with the same
     * columns as the source. The live shared DB has a polluted migration history
     * (duplicate create-migration records), so this is the authoritative check
     * that the tenant schema can accept the copied rows.
     *
     * @throws \RuntimeException when any table has drifted
     */
    public function assertSchemaParity(Company $company, string $sourceConnection, string $tenantConnection): void
    {
        $errors = [];

        foreach ($this->allManifestTables() as $table) {
            if (!Schema::connection($sourceConnection)->hasTable($table)) {
                $errors[] = "Source table missing: {$table}";
                continue;
            }

            if (!Schema::connection($tenantConnection)->hasTable($table)) {
                $errors[] = "Tenant table missing: {$table}";
                continue;
            }

            $sourceColumns = $this->columnList($sourceConnection, $table);
            $tenantColumns = $this->columnList($tenantConnection, $table);

            $missing = array_values(array_diff($sourceColumns, $tenantColumns));
            $extra = array_values(array_diff($tenantColumns, $sourceColumns));

            if ($missing !== [] || $extra !== []) {
                $errors[] = sprintf(
                    'Schema drift on `%s`: missing in tenant [%s], extra in tenant [%s]',
                    $table,
                    implode(', ', $missing),
                    implode(', ', $extra),
                );
            }
        }

        if ($errors !== []) {
            throw new \RuntimeException("Tenant schema parity check failed for company [{$company->id}]:\n- " . implode("\n- ", $errors));
        }
    }

    /**
     * All tables the migrator writes to (direct + chained + reference + personal).
     */
    public function allManifestTables(): array
    {
        return array_values(array_unique(array_merge(
            self::DIRECT_TABLES,
            array_keys(self::CHAIN_TABLES),
            self::ALL_ROWS_TABLES,
            self::MEMBERSHIP_TABLES,
        )));
    }

    /**
     * Run a scoped SELECT against the source database for a manifest table,
     * returning the first row (or null when nothing matches).
     *
     * @param string $select SQL select-list, e.g. `COUNT(*) AS `count``
     */
    public function sourceSelect(string $table, Company $company, string $sourceConnection, string $select): ?object
    {
        $sourceDb = DB::connection($sourceConnection)->getDatabaseName();

        if (in_array($table, self::DIRECT_TABLES, true)) {
            return DB::connection($sourceConnection)->selectOne(
                "SELECT {$select} FROM `{$sourceDb}`.`{$table}` WHERE `company_id` = ?",
                [$company->id]
            );
        }

        if (isset(self::CHAIN_TABLES[$table])) {
            $predicate = str_replace('{source}', '`' . $sourceDb . '`', self::CHAIN_TABLES[$table]);
            $bindings = array_fill(0, substr_count($predicate, '?'), $company->id);

            return DB::connection($sourceConnection)->selectOne(
                "SELECT {$select} FROM `{$sourceDb}`.`{$table}` s WHERE {$predicate}",
                $bindings
            );
        }

        if (in_array($table, self::ALL_ROWS_TABLES, true)) {
            return DB::connection($sourceConnection)->selectOne(
                "SELECT {$select} FROM `{$sourceDb}`.`{$table}`"
            );
        }

        if (in_array($table, self::MEMBERSHIP_TABLES, true)) {
            return DB::connection($sourceConnection)->selectOne(
                "SELECT {$select} FROM `{$sourceDb}`.`{$table}` WHERE `user_id` IN (SELECT `user_id` FROM `{$sourceDb}`.`company_user` WHERE `company_id` = ?)",
                [$company->id]
            );
        }

        throw new \InvalidArgumentException("Table [{$table}] is not part of the migration manifest.");
    }

    /**
     * Number of source rows the migrator would copy for a given manifest table.
     */
    public function sourceCount(string $table, Company $company, string $sourceConnection): int
    {
        $row = $this->sourceSelect($table, $company, $sourceConnection, 'COUNT(*) AS `count`');

        return (int) ($row->count ?? 0);
    }

    private function insertTenantCompanyRow(Company $company, string $tenantConnection): void
    {
        DB::connection($tenantConnection)->table('companies')->updateOrInsert(
            ['id' => $company->id],
            ['created_at' => now(), 'updated_at' => now()],
        );
    }

    private function copyRows(string $tenantConnection, string $sourceConnection, string $table, string $sqlTemplate, array $bindings = []): int
    {
        $columns = $this->columnList($tenantConnection, $table);
        $quoted = implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $columns));

        $sql = str_replace('{columns}', $quoted, $sqlTemplate);

        return DB::connection($tenantConnection)->statement($sql, $bindings)
            ? DB::connection($tenantConnection)->table($table)->count()
            : 0;
    }

    /**
     * Seed minimal stub `users` rows (id + timestamps only) so the tenant's
     * foreign keys to users resolve. Includes the tenant's own copied rows plus
     * the company's central membership list.
     */
    private function seedStubUsers(Company $company, string $tenantConnection, string $sourceConnection, string $sourceDb): void
    {
        foreach (self::USER_FK_COLUMNS as $table => $columns) {
            if (!Schema::connection($tenantConnection)->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                DB::connection($tenantConnection)->statement(
                    "INSERT IGNORE INTO `users` (`id`, `created_at`, `updated_at`) "
                    . "SELECT DISTINCT `{$column}`, NOW(), NOW() FROM `{$table}` WHERE `{$column}` IS NOT NULL"
                );
            }
        }

        $modelType = 'App\\Models\\User';

        DB::connection($tenantConnection)->statement(
            "INSERT IGNORE INTO `users` (`id`, `created_at`, `updated_at`) "
            . "SELECT DISTINCT `model_id`, NOW(), NOW() FROM `model_has_roles` WHERE `model_type` = ? AND `model_id` IS NOT NULL",
            [$modelType],
        );

        DB::connection($tenantConnection)->statement(
            "INSERT IGNORE INTO `users` (`id`, `created_at`, `updated_at`) "
            . "SELECT DISTINCT `model_id`, NOW(), NOW() FROM `model_has_permissions` WHERE `model_type` = ? AND `model_id` IS NOT NULL",
            [$modelType],
        );

        if (Schema::connection($sourceConnection)->hasTable('company_user')) {
            DB::connection($tenantConnection)->statement(
                "INSERT IGNORE INTO `users` (`id`, `created_at`, `updated_at`) "
                . "SELECT `user_id`, NOW(), NOW() FROM `{$sourceDb}`.`company_user` WHERE `company_id` = ?",
                [$company->id],
            );
        }
    }

    private function assertSourceAndTargetOnSameServer(string $sourceConnection, string $tenantConnection): void
    {
        $source = DB::connection($sourceConnection)->getConfig('host');
        $target = DB::connection($tenantConnection)->getConfig('host');

        if ($source !== $target) {
            throw new \RuntimeException(
                "Source and tenant databases must live on the same MySQL server to use cross-database INSERT...SELECT (source host [{$source}], tenant host [{$target}])."
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function columnList(string $connection, string $table): array
    {
        $key = $connection . '::' . $table;

        if (!isset($this->columnCache[$key])) {
            $this->columnCache[$key] = Schema::connection($connection)->getColumnListing($table);
        }

        return $this->columnCache[$key];
    }
}

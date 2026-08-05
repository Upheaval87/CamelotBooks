<?php

namespace App\Services\Verification;

use App\Models\Company;
use App\Services\Tenancy\CompanyDataMigrator;
use Illuminate\Support\Facades\DB;

/**
 * Verifies a Phase 2 company migration by comparing the shared database with the
 * tenant database. Re-runnable at any point during the retention window.
 */
class CompanyMigrationVerifier
{
    private const TRIAL_BALANCE_STATUSES = ['posted', 'reversed'];

    /**
     * [child_table, child_fk_column, parent_table, parent_pk_column]
     */
    private const FK_ORPHAN_CHECKS = [
        ['journal_entry_lines', 'journal_entry_id', 'journal_entries', 'id'],
        ['invoice_lines', 'invoice_id', 'invoices', 'id'],
        ['bill_lines', 'bill_id', 'bills', 'id'],
        ['journal_entry_lines', 'account_id', 'accounts', 'id'],
        ['journal_entry_lines', 'branch_id', 'branches', 'id'],
        ['invoice_lines', 'product_id', 'products', 'id'],
        ['accounts', 'parent_id', 'accounts', 'id'],
        ['invoices', 'customer_id', 'customers', 'id'],
        ['bills', 'vendor_id', 'vendors', 'id'],
        ['customer_payments', 'customer_id', 'customers', 'id'],
        ['vendor_payments', 'vendor_id', 'vendors', 'id'],
    ];

    /**
     * [table, columns to SUM] — aggregate spot checks comparing source vs tenant.
     * Only tables/columns confirmed to exist are listed (journal_entries stores
     * amounts only via its lines, so it is deliberately excluded).
     */
    private const SUM_SPOT_CHECKS = [
        'accounts' => ['opening_balance'],
        'invoices' => ['amount', 'amount_paid'],
        'bills' => ['amount'],
        'journal_entry_lines' => ['debit', 'credit'],
    ];

    public function __construct(private readonly CompanyDataMigrator $migrator)
    {
    }

    /**
     * @return array{passed: bool, checks: array<int, array<string, mixed>>}
     */
    public function verify(Company $company, string $tenantConnection, string $sourceConnection = 'mysql'): array
    {
        $checks = [];

        foreach ($this->migrator->allManifestTables() as $table) {
            $checks[] = $this->countCheck($company, $tenantConnection, $sourceConnection, $table);
        }

        foreach (self::SUM_SPOT_CHECKS as $table => $columns) {
            $checks[] = $this->aggregateCheck($company, $tenantConnection, $sourceConnection, $table, $columns);
        }

        $checks[] = $this->stubCheck($tenantConnection);
        $checks[] = $this->trialBalanceCheck($tenantConnection);

        foreach (self::FK_ORPHAN_CHECKS as [$child, $childFk, $parent, $parentPk]) {
            $checks[] = $this->fkOrphanCheck($tenantConnection, $child, $childFk, $parent, $parentPk);
        }

        $passed = collect($checks)->every(fn (array $c) => $c['status'] === 'passed');

        return ['passed' => $passed, 'checks' => $checks];
    }

    /**
     * @return array<string, mixed>
     */
    private function countCheck(Company $company, string $tenantConnection, string $sourceConnection, string $table): array
    {
        $source = $this->migrator->sourceCount($table, $company, $sourceConnection);
        $actual = (int) DB::connection($tenantConnection)->table($table)->count();

        return $this->result(
            "row_count.{$table}",
            $source,
            $actual,
            $source === $actual,
            "{$table} row count",
        );
    }

    /**
     * @param array<int, string> $sumColumns
     *
     * @return array<string, mixed>
     */
    private function aggregateCheck(Company $company, string $tenantConnection, string $sourceConnection, string $table, array $sumColumns): array
    {
        $sumClause = implode(', ', array_map(
            static fn (string $c): string => "COALESCE(SUM(`{$c}`), 0) AS `sum_{$c}`",
            $sumColumns,
        ));

        $sourceRow = $this->migrator->sourceSelect(
            $table,
            $company,
            $sourceConnection,
            "COUNT(*) AS `count`, {$sumClause}",
        );
        $tenantRow = DB::connection($tenantConnection)->selectOne(
            "SELECT COUNT(*) AS `count`, {$sumClause} FROM `{$table}`"
        );

        $expected = $this->flattenAggregate($sourceRow, $sumColumns);
        $actual = $this->flattenAggregate($tenantRow, $sumColumns);

        return $this->result(
            "aggregate.{$table}",
            $expected,
            $actual,
            $expected === $actual,
            "{$table} aggregates (count + " . implode(', ', $sumColumns) . ')',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function stubCheck(string $tenantConnection): array
    {
        $companyRows = (int) DB::connection($tenantConnection)->table('companies')->count();
        $userRows = (int) DB::connection($tenantConnection)->table('users')->count();

        $ok = $companyRows === 1 && $userRows >= 1;

        return $this->result(
            'stub_rows',
            'companies=1, users>=1',
            "companies={$companyRows}, users={$userRows}",
            $ok,
            'tenant companies/users stub rows',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function trialBalanceCheck(string $tenantConnection): array
    {
        $statuses = "'" . implode("', '", self::TRIAL_BALANCE_STATUSES) . "'";
        $row = DB::connection($tenantConnection)->selectOne(
            "SELECT COALESCE(SUM(`jel`.`debit`), 0) AS `debit`, COALESCE(SUM(`jel`.`credit`), 0) AS `credit`
             FROM `journal_entry_lines` `jel`
             JOIN `journal_entries` `je` ON `je`.`id` = `jel`.`journal_entry_id`
             WHERE `je`.`status` IN ({$statuses})"
        );

        $debit = rtrim(rtrim((string) $row->debit, '0'), '.');
        $credit = rtrim(rtrim((string) $row->credit, '0'), '.');
        $ok = $debit === $credit;

        return $this->result(
            'trial_balance',
            'debits = credits',
            "debit={$row->debit}, credit={$row->credit}",
            $ok,
            'posted/reversed journal entry lines net to zero',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fkOrphanCheck(string $tenantConnection, string $child, string $childFk, string $parent, string $parentPk): array
    {
        $count = (int) DB::connection($tenantConnection)->selectOne(
            "SELECT COUNT(*) AS `c` FROM `{$child}` `c`
             LEFT JOIN `{$parent}` `p` ON `p`.`id` = `c`.`{$childFk}`
             WHERE `c`.`{$childFk}` IS NOT NULL AND `p`.`id` IS NULL"
        )->c;

        return $this->result(
            "fk_orphans.{$child}.{$childFk}",
            0,
            $count,
            $count === 0,
            "orphans in {$child}.{$childFk} -> {$parent}",
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function result(string $name, mixed $expected, mixed $actual, bool $ok, string $detail): array
    {
        return [
            'name' => $name,
            'status' => $ok ? 'passed' : 'failed',
            'expected' => $expected,
            'actual' => $actual,
            'detail' => $detail,
        ];
    }

    /**
     * @param array<int, string> $sumColumns
     *
     * @return array<string, string>
     */
    private function flattenAggregate(object $row, array $sumColumns): array
    {
        $flat = ['count' => (string) $row->count];

        foreach ($sumColumns as $column) {
            $flat['sum_' . $column] = $this->normaliseDecimal((string) ($row->{'sum_' . $column} ?? '0'));
        }

        return $flat;
    }

    private function normaliseDecimal(string $value): string
    {
        return rtrim(rtrim($value, '0'), '.');
    }
}

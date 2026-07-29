<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Bills
            'bills.create', 'bills.view', 'bills.edit', 'bills.delete',
            'bills.post', 'bills.approve', 'bills.void',
            // Invoices
            'invoices.create', 'invoices.view', 'invoices.edit', 'invoices.delete',
            'invoices.post', 'invoices.void',
            // Journal Entries
            'journal-entries.create', 'journal-entries.view', 'journal-entries.edit', 'journal-entries.delete',
            'journal-entries.post', 'journal-entries.approve', 'journal-entries.reverse',
            // Expenses
            'expenses.create', 'expenses.view', 'expenses.edit', 'expenses.delete',
            'expenses.post', 'expenses.approve',
            // Budgets
            'budgets.create', 'budgets.view', 'budgets.edit', 'budgets.delete', 'budgets.lock',
            // Accounting Periods
            'accounting-periods.view', 'accounting-periods.create', 'accounting-periods.close',
            'accounting-periods.lock', 'accounting-periods.reopen',
            // Reports
            'reports.view', 'reports.export',
            // Banking
            'banking.reconcile', 'bank-accounts.manage',
            // Tax
            'tax-rates.manage', 'tax-returns.create', 'tax-returns.view', 'tax-returns.submit',
            // Chart of Accounts
            'chart-of-accounts.view', 'chart-of-accounts.manage',
            // Admin
            'company-settings.manage', 'users.manage',
            'audit-log.view', 'audit-log.export', 'backups.manage',
            // Banking-specific
            'bank-accounts.view',
            // System (global only)
            'system.access', 'system.settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // --- system_admin (global, no company_id) ---
        $systemAdmin = Role::create(['name' => 'system_admin', 'guard_name' => 'web', 'company_id' => null]);
        $systemAdmin->givePermissionTo(Permission::all());

        // --- company_admin (per-company) ---
        $companyAdmin = Role::create(['name' => 'company_admin', 'guard_name' => 'web']);
        $companyAdmin->givePermissionTo(Permission::all());

        // --- accountant (per-company) ---
        $accountant = Role::create(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->givePermissionTo([
            // View all
            'bills.view', 'invoices.view', 'journal-entries.view', 'expenses.view',
            'budgets.view', 'accounting-periods.view', 'reports.view',
            'audit-log.view', 'tax-returns.view', 'chart-of-accounts.view',
            'bank-accounts.view',
            // Create + Edit
            'bills.create', 'bills.edit',
            'invoices.create', 'invoices.edit',
            'journal-entries.create', 'journal-entries.edit',
            'expenses.create', 'expenses.edit',
            'budgets.create', 'budgets.edit',
            'tax-returns.create',
            // Post (but not approve/void/delete)
            'bills.post',
            'invoices.post',
            'journal-entries.post',
            'expenses.post',
            // Reports
            'reports.export',
            // Banking
            'banking.reconcile',
        ]);

        // --- approver (per-company) ---
        $approver = Role::create(['name' => 'approver', 'guard_name' => 'web']);
        $approver->givePermissionTo([
            'bills.view', 'bills.approve',
            'invoices.view',
            'journal-entries.view', 'journal-entries.approve',
            'expenses.view', 'expenses.approve',
            'budgets.view',
            'accounting-periods.view',
            'reports.view',
            'audit-log.view',
            'chart-of-accounts.view',
            'tax-returns.view',
        ]);

        // --- viewer (per-company) ---
        $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo([
            'bills.view',
            'invoices.view',
            'journal-entries.view',
            'expenses.view',
            'budgets.view',
            'accounting-periods.view',
            'reports.view',
            'audit-log.view',
            'chart-of-accounts.view',
            'tax-returns.view',
        ]);
    }
}

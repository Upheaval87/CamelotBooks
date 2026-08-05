<?php

namespace App\Services;

class ModuleRegistry
{
    public static function catalog(): array
    {
        $catalog = [];
        $sort = 0;

        $core = [
            'accounting' => ['name' => 'Accounting', 'description' => 'Chart of Accounts, Journal, General Ledger, Trial Balance'],
            'sales' => ['name' => 'Sales', 'description' => 'Invoices, Customers, Sales Receipts, Credit Notes, Quotations'],
            'reports' => ['name' => 'Reports', 'description' => 'Report center and financial statements'],
            'system' => ['name' => 'System', 'description' => 'System settings and configuration'],
            'users' => ['name' => 'Users', 'description' => 'User management and roles'],
        ];

        foreach ($core as $code => $data) {
            $sort += 10;
            $catalog[$code] = [
                'name' => $data['name'],
                'description' => $data['description'],
                'is_core' => true,
                'sort_order' => $sort,
            ];
        }

        foreach (FeatureManagement::getAvailableFeatures() as $code => $name) {
            $sort += 10;
            $catalog[$code] = [
                'name' => $name,
                'description' => null,
                'is_core' => false,
                'sort_order' => $sort,
            ];
        }

        return $catalog;
    }
}

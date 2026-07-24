<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Product;
use App\Services\POS\PosSetupService;
use Illuminate\Database\Seeder;

class PosTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        PosSetupService::seedDefaultsForCompany($companyId);

        $revenueAccount = Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );

        $products = [
            ['name' => 'Blue Pen', 'sku' => 'PEN-001', 'sales_price' => 2.50, 'purchase_price' => 1.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'A4 Notebook', 'sku' => 'NB-001', 'sales_price' => 5.00, 'purchase_price' => 2.50, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'Stapler', 'sku' => 'STP-001', 'sales_price' => 12.00, 'purchase_price' => 6.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'USB Flash Drive 16GB', 'sku' => 'USB-016', 'sales_price' => 15.00, 'purchase_price' => 8.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'Wireless Mouse', 'sku' => 'MS-001', 'sales_price' => 25.00, 'purchase_price' => 12.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'HDMI Cable 2m', 'sku' => 'HDMI-002', 'sales_price' => 8.00, 'purchase_price' => 3.50, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'Desk Lamp', 'sku' => 'LAMP-001', 'sales_price' => 35.00, 'purchase_price' => 18.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'Power Bank 10000mAh', 'sku' => 'PB-10K', 'sales_price' => 22.00, 'purchase_price' => 11.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'File Folder (Pack of 10)', 'sku' => 'FF-010', 'sales_price' => 6.50, 'purchase_price' => 3.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
            ['name' => 'Whiteboard Marker (Box)', 'sku' => 'WBM-BOX', 'sales_price' => 10.00, 'purchase_price' => 5.00, 'tax_rate' => 16.00, 'is_taxable' => true, 'tracked_as_inventory' => false],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(
                ['company_id' => $companyId, 'sku' => $p['sku']],
                array_merge($p, [
                    'type' => 'goods',
                    'is_active' => true,
                    'income_account_id' => $revenueAccount->id,
                ])
            );
        }

        $customers = [
            ['name' => 'Walk-in Customer', 'email' => null, 'phone' => null],
            ['name' => 'John Banda', 'email' => 'john@example.com', 'phone' => '+265 991 234 567'],
            ['name' => 'Mary Phiri', 'email' => 'mary@example.com', 'phone' => '+265 882 345 678'],
            ['name' => 'Peter Mwangonde', 'email' => 'peter@example.com', 'phone' => '+265 773 456 789'],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['company_id' => $companyId, 'name' => $c['name']],
                [
                    'email' => $c['email'],
                    'phone' => $c['phone'],
                    'is_active' => true,
                ]
            );
        }

        echo "Seeded " . count($products) . " products and " . count($customers) . " customers for Acme Corporation.\n";
    }
}

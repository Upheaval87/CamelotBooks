<?php

namespace App\Services\POS;

use App\Models\Account;
use App\Models\PosPaymentMethod;

class PosSetupService
{
    public static function seedDefaultsForCompany(int $companyId): void
    {
        Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '1050'],
            [
                'name' => 'Undeposited Funds',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Cash and cheques received but not yet deposited',
                'is_active' => true,
            ]
        );

        $cashInDrawer = Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '1060'],
            [
                'name' => 'Cash-in-Drawer',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Cash held in POS terminals before bank deposit',
                'is_active' => true,
            ]
        );

        $cardClearing = Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '1070'],
            [
                'name' => 'Card Clearing',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Card payments owed by processor, pending settlement',
                'is_active' => true,
            ]
        );

        $mobileMoneyClearing = Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '1080'],
            [
                'name' => 'Mobile Money Clearing',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Mobile money payments owed by network, pending settlement',
                'is_active' => true,
            ]
        );

        PosPaymentMethod::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Cash'],
            [
                'type' => 'cash',
                'clearing_account_id' => $cashInDrawer->id,
                'requires_reference' => false,
                'is_active' => true,
            ]
        );

        PosPaymentMethod::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Card'],
            [
                'type' => 'card',
                'clearing_account_id' => $cardClearing->id,
                'requires_reference' => true,
                'is_active' => true,
            ]
        );

        PosPaymentMethod::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Mobile Money'],
            [
                'type' => 'mobile_money',
                'clearing_account_id' => $mobileMoneyClearing->id,
                'requires_reference' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '7400'],
            [
                'name' => 'Cash Overage',
                'type' => 'income',
                'sub_type' => 'other_income',
                'description' => 'Cash received above expected amount at till close',
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '6900'],
            [
                'name' => 'Cash Shortage',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Cash received below expected amount at till close',
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['company_id' => $companyId, 'code' => '6950'],
            [
                'name' => 'Merchant Processing Fees',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Fees charged by card/mobile money payment processors',
                'is_active' => true,
            ]
        );
    }
}

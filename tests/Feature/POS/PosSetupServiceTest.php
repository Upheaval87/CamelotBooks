<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\Company;
use App\Models\PosPaymentMethod;
use App\Services\POS\PosSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSetupServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'POS Setup Co',
            'company_code' => 'PSC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
    }

    public function test_creates_three_clearing_accounts(): void
    {
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $cashInDrawer = Account::where('company_id', $this->company->id)->where('code', '1060')->first();
        $cardClearing = Account::where('company_id', $this->company->id)->where('code', '1070')->first();
        $mobileMoney = Account::where('company_id', $this->company->id)->where('code', '1080')->first();

        $this->assertNotNull($cashInDrawer);
        $this->assertEquals('Cash-in-Drawer', $cashInDrawer->name);
        $this->assertEquals('asset', $cashInDrawer->type);
        $this->assertEquals('current_asset', $cashInDrawer->sub_type);

        $this->assertNotNull($cardClearing);
        $this->assertEquals('Card Clearing', $cardClearing->name);

        $this->assertNotNull($mobileMoney);
        $this->assertEquals('Mobile Money Clearing', $mobileMoney->name);
    }

    public function test_creates_three_default_payment_methods(): void
    {
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $cash = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();
        $card = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Card')->first();
        $momo = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Mobile Money')->first();

        $this->assertNotNull($cash);
        $this->assertEquals('cash', $cash->type);
        $this->assertFalse($cash->requires_reference);

        $this->assertNotNull($card);
        $this->assertEquals('card', $card->type);
        $this->assertTrue($card->requires_reference);

        $this->assertNotNull($momo);
        $this->assertEquals('mobile_money', $momo->type);
        $this->assertTrue($momo->requires_reference);
    }

    public function test_payment_methods_reference_correct_clearing_accounts(): void
    {
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $cash = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();
        $card = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Card')->first();
        $momo = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Mobile Money')->first();

        $cashAccount = Account::where('company_id', $this->company->id)->where('code', '1060')->first();
        $cardAccount = Account::where('company_id', $this->company->id)->where('code', '1070')->first();
        $momoAccount = Account::where('company_id', $this->company->id)->where('code', '1080')->first();

        $this->assertEquals($cashAccount->id, $cash->clearing_account_id);
        $this->assertEquals($cardAccount->id, $card->clearing_account_id);
        $this->assertEquals($momoAccount->id, $momo->clearing_account_id);
    }

    public function test_is_idempotent(): void
    {
        PosSetupService::seedDefaultsForCompany($this->company->id);
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $this->assertEquals(3, Account::where('company_id', $this->company->id)->whereIn('code', ['1060', '1070', '1080'])->count());
        $this->assertEquals(4, PosPaymentMethod::where('company_id', $this->company->id)->count());
    }

    public function test_accounts_do_not_leak_between_companies(): void
    {
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $companyB = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->assertEquals(0, Account::where('company_id', $companyB->id)->whereIn('code', ['1060', '1070', '1080'])->count());
        $this->assertEquals(0, PosPaymentMethod::where('company_id', $companyB->id)->count());
    }

    public function test_all_accounts_are_active_current_assets(): void
    {
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $accounts = Account::where('company_id', $this->company->id)->whereIn('code', ['1060', '1070', '1080'])->get();

        foreach ($accounts as $account) {
            $this->assertTrue($account->is_active);
            $this->assertEquals('asset', $account->type);
            $this->assertEquals('current_asset', $account->sub_type);
        }
    }

    public function test_payment_methods_are_active(): void
    {
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $methods = PosPaymentMethod::where('company_id', $this->company->id)->get();

        foreach ($methods as $method) {
            $this->assertTrue($method->is_active);
        }
    }
}

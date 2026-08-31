<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\BankDeposit;
use App\Models\BankDepositLine;
use App\Models\BankTransaction;
use App\Models\Bill;
use App\Models\Cheque;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesReceipt;
use App\Models\SalesReceiptPayment;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Models\Vendor;
use Database\Seeders\AccountingBankingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Accounting + Banking seeder produces a small Malawian demo company
 * ("Chimwemwe Trading", MWK) with master data + posted AR/AP/banking activity.
 */
class AccountingBankingSeederTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected int $userId;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => AccountingBankingSeeder::COMPANY_NAME,
            'company_code' => 'CHIM',
            'base_currency' => 'MWK',
            'is_active' => true,
            'provisioning_status' => Company::STATUS_PENDING,
        ]);

        UserCompanyAssignment::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'role' => 'company_admin',
            'branch_ids' => [],
            'is_active' => true,
        ]);

        $this->userId = $this->user->id;
    }

    private function actingCompany(string $role = 'company_admin')
    {
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole($role);

        return $this->actingAs($this->user)->withSession(['current_company_id' => $this->company->id]);
    }

    private function seedCompany(): array
    {
        return (new AccountingBankingSeeder)->seed($this->company->id, $this->userId);
    }

    public function test_seeder_is_idempotent_across_reruns(): void
    {
        $first = $this->seedCompany();
        $second = $this->seedCompany();

        $this->assertSame($first['counts'], $second['counts']);
        $this->assertSame(6, $this->company->customers()->count());
    }

    public function test_seeder_creates_expected_row_counts(): void
    {
        $result = $this->seedCompany();
        $counts = $result['counts'];

        // Master data.
        $this->assertSame(6, $counts['customers']);
        $this->assertSame(4, $counts['vendors']);
        $this->assertSame(10, $counts['products']);
        $this->assertSame(5, $counts['employees']);
        $this->assertGreaterThan(30, $counts['accounts']);

        // Document cycle.
        $this->assertSame(3, $counts['invoices']);
        $this->assertSame(2, $counts['bills']);
        // 3 settlement receipts (AR cycle) + 3 standalone undeposited receipts.
        $this->assertSame(6, $counts['sales_receipts']);
        $this->assertSame(1, $counts['bank_deposits']);

        // Activity.
        $this->assertGreaterThan(8, $counts['journal_entries']);
        $this->assertGreaterThan(5, $counts['bank_transactions']);
        $this->assertSame(1, $counts['cheques']);

        $this->assertSame($this->company->id, $result['company_id']);
    }

    public function test_invoices_are_posted_with_nontrivial_amounts(): void
    {
        $this->seedCompany();

        $invoices = Invoice::where('company_id', $this->company->id)->get();

        $this->assertCount(3, $invoices);
        foreach ($invoices as $invoice) {
            $this->assertGreaterThan(0, (float) $invoice->amount);
            $this->assertNotNull($invoice->journal_entry_id, "Invoice {$invoice->invoice_number} not posted");
        }

        // Invoice 2 is settled by a CASH receipt -> the invoice should be PAID.
        // The other two are partially/un-settled (still SENT).
        $paid = $invoices->get(2);
        $this->assertSame(Invoice::STATUS_PAID, $paid->status);
    }

    public function test_bills_exist_with_one_posted(): void
    {
        $this->seedCompany();

        $bills = Bill::where('company_id', $this->company->id)->get();

        $this->assertCount(2, $bills);
        $posted = $bills->filter(fn ($b) => $b->status === Bill::STATUS_APPROVED);
        $draft = $bills->filter(fn ($b) => $b->status === Bill::STATUS_DRAFT);

        $this->assertCount(1, $posted);
        $this->assertCount(1, $draft);
    }

    public function test_bank_deposit_claims_the_two_undeposited_receipts(): void
    {
        $this->seedCompany();

        $deposit = BankDeposit::where('company_id', $this->company->id)->first();
        $this->assertNotNull($deposit);

        $this->assertSame(BankDeposit::STATUS_POSTED, $deposit->status);

        $lineCount = BankDepositLine::where('deposit_id', $deposit->id)->count();
        $this->assertSame(2, $lineCount);

        // Exactly the two undeposited settlement receipts are claimed by the deposit.
        $claimed = SalesReceipt::where('company_id', $this->company->id)
            ->whereNotNull('deposit_id')
            ->count();
        $this->assertSame(2, $claimed);

        // The CASH receipt is NOT deposited (clears to 1060).
        $cash = SalesReceipt::where('company_id', $this->company->id)
            ->where('reference', 'PAY-003')
            ->first();
        $this->assertNotNull($cash);
        $this->assertNull($cash->deposit_id);
        $this->assertNotNull($cash->journal_entry_id);
    }

    public function test_seeder_leaves_three_undeposited_receipts_for_deposit(): void
    {
        $this->seedCompany();

        // The three standalone receipts clear to 1050 (Un-deposited Funds) and
        // are NOT claimed by any deposit, so they remain available to deposit.
        $undeposited = SalesReceipt::where('company_id', $this->company->id)
            ->whereIn('reference', ['UNP-001', 'UNP-002', 'UNP-003'])
            ->orderBy('reference')
            ->get();

        $this->assertCount(3, $undeposited);

        foreach ($undeposited as $receipt) {
            $this->assertSame(SalesReceipt::STATUS_POSTED, $receipt->status);
            $this->assertNull($receipt->invoice_id, "{$receipt->reference} should be standalone");
            $this->assertNull($receipt->deposit_id, "{$receipt->reference} should be un-deposited");
            $this->assertNotNull($receipt->journal_entry_id, "{$receipt->reference} should be posted");
            $this->assertGreaterThan(0, (float) $receipt->total);
        }

        // They surface on the Deposits page's Undeposited Receipts card (the
        // card renders each line's sequential receipt number).
        $numbers = $undeposited->pluck('receipt_number');
        $this->assertCount(3, $numbers);

        $response = $this->actingCompany()->get('/accounting/banking/deposits');
        $response->assertStatus(200);
        $response->assertSee('Undeposited Receipts');
        foreach ($numbers as $number) {
            $response->assertSee($number, false);
        }
    }

    public function test_banking_ops_generate_transactions_and_a_cheque(): void
    {
        $this->seedCompany();

        // Transfer + petty establish + 2 expenses + replenish + cheque = 6 bank transactions.
        $txns = BankTransaction::where('company_id', $this->company->id)->get();
        $this->assertGreaterThanOrEqual(6, $txns->count());

        $cheque = Cheque::where('company_id', $this->company->id)->first();
        $this->assertNotNull($cheque);
        $this->assertSame(Cheque::STATUS_OUTSTANDING, $cheque->status);
        $this->assertSame(65000.0, (float) $cheque->amount);
        $this->assertNotNull($cheque->bank_account_id);
    }

    public function test_opening_balances_and_ledger_are_balanced(): void
    {
        $this->seedCompany();

        $this->assertGreaterThan(0, JournalEntry::where('company_id', $this->company->id)->count());

        // Retained Earnings and the 1000 bank account both carry opening balances.
        $retained = Account::where('company_id', $this->company->id)->where('code', '3100')->first();
        $this->assertNotNull($retained);

        // Every posted journal entry must balance (sum debit == sum credit).
        foreach (JournalEntry::where('company_id', $this->company->id)->get() as $je) {
            $this->assertGreaterThan(0, $je->lines()->count(), "JE {$je->id} has no lines");
            $dr = (float) $je->lines()->sum('debit');
            $cr = (float) $je->lines()->sum('credit');
            $this->assertEqualsWithDelta($dr, $cr, 0.01, "JE {$je->id} is unbalanced: dr {$dr} cr {$cr}");
        }
    }

    public function test_trial_balance_report_renders_for_seeded_company(): void
    {
        $this->seedCompany();

        $response = $this->actingCompany()->get('/accounting/trial-balance');
        $response->assertStatus(200);
        $response->assertSee('Trial Balance');
    }

    public function test_banking_dashboard_renders_for_seeded_company(): void
    {
        $this->seedCompany();

        $response = $this->actingCompany()->get('/accounting/banking');
        $response->assertStatus(200);
    }

    public function test_deposits_index_renders_and_content_is_coherent(): void
    {
        $this->seedCompany();

        $response = $this->actingCompany()->get('/accounting/banking/deposits');
        $response->assertStatus(200);

        $deposit = BankDeposit::where('company_id', $this->company->id)->where('reference', 'DEP-WK1')->first();
        $this->assertNotNull($deposit);

        // The mockup design shows the deposit volume via the KPI row (Deposits
        // This Month) and the undeposited-receipts card — not a recent-deposits table.
        $response->assertSee('dp2-suite');
        $response->assertSee('Deposits This Month');
        $response->assertSee('Undeposited Receipts');
    }
}

<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\BankDeposit;
use App\Models\BankDepositLine;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PosPaymentMethod;
use App\Models\SalesReceipt;
use App\Models\SalesReceiptPayment;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Admin\NumberingSequenceService;
use App\Services\FeatureManagement;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
/**
 * Banking Deposits durable flow:
 *  - undeposited 1050-debit lines (from POS/sales-receipt clearing) are picked up;
 *  - create (draft) + post (save & post) with Dr bank / Cr Undeposited Funds JE;
 *  - a durable BankTransaction row + sales_receipts.deposit_id stamp;
 *  - double-deposit guard; void reversal releases the receipts;
 *  - permission gating (create/void/view).
 */
class BankDepositTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected int $userId;
    protected User $user;
    protected Account $bank;
    protected Account $undeposited;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();

        $this->company = Company::create([
            'name' => 'Deposit Co',
            'company_code' => 'DPST',
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

        $this->bank = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Main Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank_account' => true,
        ]);

        $this->undeposited = Account::create([
            'company_id' => $this->company->id,
            'code' => '1050',
            'name' => 'Undeposited Funds',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        DefaultAccountMapping::setMapping($this->company->id, 'undeposited_funds', $this->undeposited->id);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q3',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'status' => 'open',
        ]);

        FeatureManagement::enable($this->company->id, 'banking');

        app(NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    private function actingCompany(string $role = 'company_admin')
    {
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole($role);

        return $this->actingAs($this->user)->withSession(['current_company_id' => $this->company->id]);
    }

    /**
     * Create a posted journal entry with an un-deposited 1050 debit line + linked sales receipt.
     */
    private function makeDepositable(string $amount = '1000.00', string $ref = 'POS-0001', ?PosPaymentMethod $paymentMethod = null): array
    {
        $entry = app(JournalPostingEngine::class)->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-08-15',
            'source_module' => 'pos',
            'reference' => $ref,
            'memo' => "Clearing $ref into undeposited funds",
            'lines' => [
                ['account_id' => $this->undeposited->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'clearing'],
                ['account_id' => $this->bank->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'clearing'],
            ],
        ]);

        $receipt = SalesReceipt::create([
            'company_id' => $this->company->id,
            'receipt_number' => $ref,
            'receipt_date' => '2026-08-15',
            'status' => SalesReceipt::STATUS_POSTED,
            'subtotal' => $amount,
            'tax_total' => 0,
            'total' => $amount,
            'currency' => 'MWK',
            'journal_entry_id' => $entry->id,
            'created_by' => $this->user->id,
        ]);

        if ($paymentMethod) {
            SalesReceiptPayment::create([
                'sales_receipt_id' => $receipt->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $amount,
                'bank_account_id' => null,
            ]);
        }

        $line = JournalEntryLine::where('journal_entry_id', $entry->id)
            ->where('account_id', $this->undeposited->id)
            ->first();

        return ['entry' => $entry, 'receipt' => $receipt, 'line' => $line];
    }

    public function test_index_renders_empty_state_and_kpis()
    {
        $this->actingCompany()
            ->get(route('accounting.banking.deposits'))
            ->assertOk()
            ->assertSee('dp2-suite')
            ->assertSee('Deposits')
            ->assertSee('Undeposited Funds')
            ->assertSee('Bank Accounts')
            ->assertSee('Deposits This Month')
            ->assertSee('Banking Centre', false)
            ->assertSee('Petty Cash', false)
            ->assertSee('No undeposited receipts.')
            ->assertSee('dp2-selbar')
            ->assertSee('chkAll', false);
    }

    public function test_create_form_renders_and_preselects_line_ids()
    {
        $data = $this->makeDepositable('500.00', 'POS-0002');

        $line = $data['line'];

        $this->actingCompany()
            ->get(route('accounting.banking.deposits.create', ['line_ids' => $line->id]))
            ->assertOk()
            ->assertSee('dp2-suite')
            ->assertSee('New Deposit')
            ->assertSee('Deposit Details')
            ->assertSee('Destination Bank Account', false)
            ->assertSee('Receipts to Deposit', false)
            ->assertSee('DEPOSIT AMOUNT')
            ->assertSee('Save &amp; Post', false);
    }

    public function test_store_posts_deposit_with_je_banktransaction_and_receipt_stamp()
    {
        $data = $this->makeDepositable('1000.00', 'POS-1000');

        $this->actingCompany()
            ->post(route('accounting.banking.deposits.store'), [
                'bank_account_id' => $this->bank->id,
                'date' => '2026-08-20',
                'reference' => 'Daily banking',
                'description' => 'Banking of the day',
                'line_ids' => [$data['line']->id],
                'action' => 'post',
            ])
            ->assertRedirect(route('accounting.banking.deposits'));

        $deposit = BankDeposit::where('company_id', $this->company->id)->first();
        $this->assertNotNull($deposit);
        $this->assertSame(BankDeposit::STATUS_POSTED, $deposit->status);
        $this->assertStringStartsWith('DEP-', $deposit->deposit_no);
        $this->assertSame(1000.00, (float) $deposit->total);

        // single line, linked to the source line
        $this->assertSame(1, $deposit->lines()->count());
        $this->assertSame($data['line']->id, $deposit->lines()->first()->source_id);

        // journal: Dr bank / Cr undeposited
        $je = $deposit->journalEntry;
        $this->assertNotNull($je);
        $this->assertSame(JournalEntry::STATUS_POSTED, $je->status);
        $this->assertSame($this->bank->id, $je->lines()->where('debit', '>', 0)->first()->account_id);
        $this->assertSame($this->undeposited->id, $je->lines()->where('credit', '>', 0)->first()->account_id);

        // durable BankTransaction
        $bt = BankTransaction::where('company_id', $this->company->id)
            ->where('source_type', 'deposit')->where('source_id', $deposit->id)->first();
        $this->assertNotNull($bt);
        $this->assertSame($deposit->deposit_no, $bt->reference);
        $this->assertSame(1000.00, (float) $bt->amount);

        // receipt stamped
        $this->assertSame($deposit->id, $data['receipt']->fresh()->deposit_id);
    }

    public function test_store_saves_as_draft_when_no_post_action()
    {
        $data = $this->makeDepositable('750.00', 'POS-0750');

        $this->actingCompany()
            ->post(route('accounting.banking.deposits.store'), [
                'bank_account_id' => $this->bank->id,
                'date' => '2026-08-20',
                'line_ids' => [$data['line']->id],
                'action' => 'draft',
            ])
            ->assertRedirect();

        $deposit = BankDeposit::where('company_id', $this->company->id)->first();
        $this->assertSame(BankDeposit::STATUS_DRAFT, $deposit->status);
        $this->assertNull($deposit->journal_entry_id);
        // nothing posted yet
        $this->assertSame(0, BankTransaction::where('company_id', $this->company->id)->count());
    }

    public function test_double_deposit_guard_rejects_reusing_a_claimed_line()
    {
        $data = $this->makeDepositable('900.00', 'POS-0900');

        $this->actingCompany()->post(route('accounting.banking.deposits.store'), [
            'bank_account_id' => $this->bank->id,
            'date' => '2026-08-20',
            'line_ids' => [$data['line']->id],
            'action' => 'post',
        ]);

        // second deposit reusing the same line must fail
        $this->actingCompany()->post(route('accounting.banking.deposits.store'), [
            'bank_account_id' => $this->bank->id,
            'date' => '2026-08-21',
            'line_ids' => [$data['line']->id],
            'action' => 'post',
        ])->assertSessionHasErrors();

        $this->assertSame(1, BankDeposit::where('company_id', $this->company->id)->count());
    }

    public function test_void_reverses_journal_and_releases_receipt()
    {
        $data = $this->makeDepositable('600.00', 'POS-0600');

        $this->actingCompany()->post(route('accounting.banking.deposits.store'), [
            'bank_account_id' => $this->bank->id,
            'date' => '2026-08-20',
            'line_ids' => [$data['line']->id],
            'action' => 'post',
        ]);

        $deposit = BankDeposit::where('company_id', $this->company->id)->first();
        $originalJe = $deposit->journal_entry_id;

        $this->actingCompany()->post(route('accounting.banking.deposits.void', $deposit->id), [
            'reason' => 'Wrong bank account',
        ])->assertRedirect();

        $deposit->refresh();
        $this->assertSame(BankDeposit::STATUS_VOID, $deposit->status);
        $this->assertSame('Wrong bank account', $deposit->void_reason);
        $this->assertNotSame($originalJe, $deposit->journal_entry_id);

        // reversal exists with linked_entry_id -> original
        $reversal = JournalEntry::find($deposit->journal_entry_id);
        $this->assertSame('reversal', $reversal->source_module);
        $this->assertSame($originalJe, $reversal->linked_entry_id);

        // receipt released
        $this->assertNull($data['receipt']->fresh()->deposit_id);
    }

    public function test_index_renders_payment_method_column_with_receipt_method()
    {
        $pm = PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Bank Transfer (Un-deposited)',
            'type' => 'bank_transfer',
            'is_active' => true,
        ]);

        $this->makeDepositable('1200.00', 'POS-MM1', $pm);

        $this->actingCompany()
            ->get(route('accounting.banking.deposits'))
            ->assertOk()
            ->assertSee('Payment Method', false)
            ->assertSee('Bank Transfer', false)
            ->assertDontSee('Bank Transfer (Un-deposited)', false)
            ->assertSee('All payment methods', false)
            ->assertSee('200.00', false);
    }

    public function test_index_filters_by_payment_method()
    {
        $pmBT = PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Bank Transfer (Un-deposited)',
            'type' => 'bank_transfer',
            'is_active' => true,
        ]);
        $pmMM = PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Mobile Money (Un-deposited)',
            'type' => 'mobile_money',
            'is_active' => true,
        ]);

        $this->makeDepositable('1100.00', 'POS-FILTER1', $pmBT);
        $this->makeDepositable('2200.00', 'POS-FILTER2', $pmMM);

        // All methods present by default
        $this->actingCompany()
            ->get(route('accounting.banking.deposits'))
            ->assertOk()
            ->assertSee('Bank Transfer', false)
            ->assertSee('Mobile Money', false);

        // Filter to Bank Transfer only
        $this->actingCompany()
            ->get(route('accounting.banking.deposits', ['payment_method' => 'Bank Transfer']))
            ->assertOk()
            ->assertSee('Bank Transfer', false)
            ->assertSee('1,100.00', false)
            ->assertDontSee('2,200.00', false);
    }

    public function test_create_form_renders_payment_method_column_and_add_receipts_modal()
    {
        $pm = PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Mobile Money (Un-deposited)',
            'type' => 'mobile_money',
            'is_active' => true,
        ]);

        $this->makeDepositable('800.00', 'POS-MM2', $pm);

        $this->actingCompany()
            ->get(route('accounting.banking.deposits.create'))
            ->assertOk()
            ->assertSee('New Deposit')
            ->assertSee('Receipts to Deposit', false)
            ->assertSee('Payment Method', false)
            ->assertSee('Mobile Money', false)
            ->assertDontSee('Mobile Money (Un-deposited)', false)
            ->assertSee('Add Undeposited Receipts', false)
            ->assertSee('dp2-modal-toolbar', false)
            ->assertSee('dp2-pm-select', false)
            ->assertSee('Add selected', false);
    }

    public function test_permission_gating_blocks_unprivileged_user()
    {
        // a plain user with no deposits permission cannot create
        $other = User::factory()->create();
        UserCompanyAssignment::create([
            'user_id' => $other->id,
            'company_id' => $this->company->id,
            'role' => 'viewer',
            'branch_ids' => [],
            'is_active' => true,
        ]);
        setPermissionsTeamId($this->company->id);
        $other->assignRole('viewer');

        $this->actingAs($other)->withSession(['current_company_id' => $this->company->id])
            ->get(route('accounting.banking.deposits.create'))
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Expense;
use App\Models\Vendor;
use App\Services\Accounting\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected ExpenseService $service;
    protected Company $company;
    protected Account $cashAccount;
    protected Account $expenseAccount;
    protected Account $taxReceivableAccount;
    protected Vendor $vendor;
    protected AccountingPeriod $period;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ExpenseService::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->taxReceivableAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1150',
            'name' => 'Tax Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        Account::create(['company_id' => $this->company->id, 'code' => '9999', 'name' => 'Rounding Differences', 'type' => 'expense', 'sub_type' => 'non_operating_expense', 'is_active' => true]);
        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');
        $mappingData = [
            'default_bank' => '1000',
            'default_expense' => '6100',
            'tax_receivable' => '1150',
            'rounding' => '9999',
        ];
        foreach ($mappingData as $key => $code) {
            if (isset($accounts[$code])) {
                \App\Models\DefaultAccountMapping::setMapping(
                    $this->company->id, $key, $accounts[$code]->id
                );
            }
        }

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Utility Company',
            'is_active' => true,
        ]);
    }

    protected function makeExpenseData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-02-15',
            'bank_account_id' => $this->cashAccount->id,
            'memo' => 'Test expense',
            'lines' => [
                [
                    'description' => 'Electricity bill',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'tax_rate' => 0,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $overrides);
    }

    public function test_create_expense(): void
    {
        $expense = $this->service->create($this->makeExpenseData(), $this->userId);

        $this->assertNotNull($expense);
        $this->assertEquals(Expense::STATUS_DRAFT, $expense->status);
        $this->assertEquals(200, $expense->amount);
        $this->assertStringStartsWith('EXP-', $expense->expense_number);
        $this->assertEquals(1, $expense->lines()->count());
    }

    public function test_create_expense_with_tax(): void
    {
        $expense = $this->service->create($this->makeExpenseData([
            'lines' => [
                [
                    'description' => 'Office supplies',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'tax_rate' => 17.5,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ]), $this->userId);

        $this->assertEquals(117.5, $expense->amount);
        $line = $expense->lines()->first();
        $this->assertEquals(100, $line->amount);
        $this->assertEquals(17.5, $line->tax_amount);
        $this->assertEquals(117.5, $line->line_total);
    }

    public function test_create_expense_requires_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->create($this->makeExpenseData(['lines' => []]), $this->userId);
    }

    public function test_post_expense_creates_journal_entry(): void
    {
        $expense = $this->service->create($this->makeExpenseData(), $this->userId);

        $posted = $this->service->post($expense, $this->userId);

        $this->assertEquals(Expense::STATUS_POSTED, $posted->status);
        $this->assertNotNull($posted->journal_entry_id);
        $this->assertNotNull($posted->posted_at);

        $je = $posted->journalEntry;
        $this->assertEquals('expense', $je->source_module);

        $lines = $je->lines()->get();
        $debits = $lines->where('debit', '>', 0);
        $credits = $lines->where('credit', '>', 0);

        $this->assertEquals(200, $debits->first()->debit);
        $this->assertEquals(200, $credits->first()->credit);
        $this->assertEquals($this->expenseAccount->id, $debits->first()->account_id);
        $this->assertEquals($this->cashAccount->id, $credits->first()->account_id);
    }

    public function test_post_expense_with_tax_creates_correct_je(): void
    {
        $expense = $this->service->create($this->makeExpenseData([
            'lines' => [
                [
                    'description' => 'Supplies',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'tax_rate' => 17.5,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ]), $this->userId);

        $posted = $this->service->post($expense, $this->userId);

        $je = $posted->journalEntry;
        $lines = $je->lines()->get();

        $expenseLine = $lines->where('account_id', $this->expenseAccount->id)->first();
        $taxLine = $lines->where('account_id', $this->taxReceivableAccount->id)->first();
        $cashLine = $lines->where('account_id', $this->cashAccount->id)->first();

        $this->assertEquals(100, $expenseLine->debit);
        $this->assertEquals(17.5, $taxLine->debit);
        $this->assertEquals(117.5, $cashLine->credit);
    }

    public function test_cannot_post_non_draft(): void
    {
        $expense = $this->service->create($this->makeExpenseData(), $this->userId);
        $this->service->post($expense, $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->service->post($expense->fresh(), $this->userId);
    }

    public function test_void_expense_reverses_je(): void
    {
        $expense = $this->service->create($this->makeExpenseData(), $this->userId);
        $this->service->post($expense, $this->userId);

        $voided = $this->service->void($expense->fresh(), 'Duplicate entry', $this->userId);

        $this->assertEquals(Expense::STATUS_VOID, $voided->status);
        $this->assertEquals('Duplicate entry', $voided->void_reason);
        $this->assertNotNull($voided->voided_at);
    }

    public function test_cannot_void_draft(): void
    {
        $expense = $this->service->create($this->makeExpenseData(), $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->service->void($expense, 'Reason', $this->userId);
    }

    public function test_expense_number_auto_increment(): void
    {
        $e1 = $this->service->create($this->makeExpenseData(), $this->userId);
        $e2 = $this->service->create($this->makeExpenseData(), $this->userId);

        $this->assertNotEquals($e1->expense_number, $e2->expense_number);
        $this->assertStringStartsWith('EXP-', $e1->expense_number);
        $this->assertStringStartsWith('EXP-', $e2->expense_number);
    }

    public function test_update_expense_in_draft(): void
    {
        $expense = $this->service->create($this->makeExpenseData(), $this->userId);

        $updated = $this->service->update($expense, [
            'memo' => 'Updated memo',
            'lines' => [
                [
                    'description' => 'Updated description',
                    'quantity' => 2,
                    'unit_price' => 150,
                    'tax_rate' => 0,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $this->assertEquals('Updated memo', $updated->memo);
        $this->assertEquals(300, $updated->amount);
    }
}

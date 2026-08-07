<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected AccountingPeriod $period;

    protected Account $expenseAccount;

    protected Vendor $vendor;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();

        $this->company = Company::create([
            'name' => 'Controller Test Co',
            'company_code' => 'BCT',
            'is_active' => true,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        $this->seed(RolePermissionSeeder::class);

        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->user->update(['current_company_id' => $this->company->id]);
        session(['current_company_id' => $this->company->id]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies Co',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $overrides);
    }

    public function test_store_persists_supplier_info_and_charges(): void
    {
        $response = $this->post(route('accounting.bills.store'), $this->payload([
            'po_number' => 'PO-0001',
            'grn_reference' => 'GRN-0001',
            'supplier_notes' => 'Ship to warehouse B.',
            'payment_instructions' => 'Bank transfer to vendor account.',
            'freight_charges' => 100,
            'insurance_charges' => 50,
            'customs_charges' => 25,
            'other_charges' => 10,
        ]));

        $response->assertRedirect();

        $bill = Bill::where('company_id', $this->company->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('PO-0001', $bill->po_number);
        $this->assertEquals('GRN-0001', $bill->grn_reference);
        $this->assertEquals('Ship to warehouse B.', $bill->supplier_notes);
        $this->assertEquals('Bank transfer to vendor account.', $bill->payment_instructions);
        $this->assertEquals(185.00, $bill->totalCharges());
        $this->assertEquals(2185.00, (float) $bill->amount);
    }

    public function test_store_rejects_negative_charges(): void
    {
        $response = $this->from(route('accounting.bills.create'))
            ->post(route('accounting.bills.store'), $this->payload([
                'freight_charges' => -5,
            ]));

        $response->assertSessionHasErrors('freight_charges');
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_show_renders_charges_and_refs(): void
    {
        $bill = app(BillService::class)->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'po_number' => 'PO-0001',
            'grn_reference' => 'GRN-0001',
            'supplier_notes' => 'Ship to warehouse B.',
            'payment_instructions' => 'Bank transfer to vendor account.',
            'freight_charges' => 100,
            'insurance_charges' => 50,
            'customs_charges' => 25,
            'other_charges' => 10,
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $this->user->id);

        $this->get(route('accounting.bills.show', $bill))
            ->assertOk()
            ->assertSee('PO-0001')
            ->assertSee('GRN-0001')
            ->assertSee('Ship to warehouse B.')
            ->assertSee('Bank transfer to vendor account.')
            ->assertSee('Freight')
            ->assertSee('Customs');
    }

    public function test_store_uploads_attachments(): void
    {
        Storage::fake('public');

        $response = $this->post(route('accounting.bills.store'), $this->payload([
            'files' => [
                File::create('invoice.pdf', 100, 'application/pdf'),
                File::create('receipt.png', 100, 'image/png'),
            ],
        ]));

        $response->assertRedirect();

        $bill = Bill::where('company_id', $this->company->id)->firstOrFail();
        $this->assertCount(2, $bill->attachments);

        foreach ($bill->attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment->file_path);
        }

        $this->assertSame('invoice.pdf', $bill->attachments[0]->name);
        $this->assertSame($this->user->id, $bill->attachments[0]->uploaded_by);
        $this->assertSame('application/pdf', $bill->attachments[0]->file_type);
    }

    public function test_update_deletes_flagged_attachment(): void
    {
        Storage::fake('public');

        $bill = app(BillService::class)->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $this->user->id);

        $attachment = $bill->attachments()->create([
            'company_id' => $this->company->id,
            'name' => 'old-receipt.pdf',
            'file_path' => 'bill-attachments/keep.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $this->user->id,
        ]);

        Storage::disk('public')->put($attachment->file_path, 'pdf-bytes');

        $response = $this->put(route('accounting.bills.update', $bill), $this->payload([
            'delete_documents' => [$attachment->id],
        ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_store_rejects_oversized_files(): void
    {
        Storage::fake('public');

        $response = $this->from(route('accounting.bills.create'))
            ->post(route('accounting.bills.store'), $this->payload([
                'files' => [File::create('huge.pdf', 11000, 'application/pdf')],
            ]));

        $response->assertSessionHasErrors('files.0');
        $this->assertDatabaseCount('bills', 0);
    }
}

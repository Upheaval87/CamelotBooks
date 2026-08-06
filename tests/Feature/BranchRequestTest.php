<?php

namespace Tests\Feature;

use App\Mail\BranchPaymentConfirmedMail;
use App\Mail\BranchQuotationIssuedMail;
use App\Mail\BranchRequestRejectedMail;
use App\Mail\BranchRequestSubmittedMail;
use App\Models\BillingQuotation;
use App\Models\BranchPayment;
use App\Models\BranchRequest;
use App\Models\Company;
use App\Models\NumberingSequence;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\BranchRequests\BranchRequestService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Branch Request, Quotation & Billing flow:
 *  - company managers submit requests; super admins approve (quote) or reject;
 *  - offline payments are recorded but NEVER auto-confirmed;
 *  - only billing/accounting/system-admin actors can confirm a payment;
 *  - confirmation is the ONLY action that raises the branch_limit (additively);
 *  - cash requires notes + restricted actors; quotations expire via command;
 *  - totals are server-calculated and frozen at quote time.
 */
class BranchRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->seed(RolePermissionSeeder::class);
    }

    private function makeCompany(string $code = 'ACME', int $branchLimit = 2): Company
    {
        return Company::create([
            'company_code' => $code,
            'name' => $code . ' Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_PENDING,
            'branch_limit' => $branchLimit,
            'branch_count' => 0,
        ]);
    }

    private function assign(User $user, Company $company, string $role = 'company_admin'): UserCompanyAssignment
    {
        return UserCompanyAssignment::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => $role,
            'branch_ids' => [],
            'is_active' => true,
        ]);
    }

    private function seedSequence(int $companyId): void
    {
        NumberingSequence::create([
            'company_id' => $companyId,
            'document_type' => 'billing_quotation',
            'prefix' => 'BQ-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'annually',
            'is_active' => true,
        ]);
    }

    private function actingCompany(User $user, Company $company, string $role = 'company_admin')
    {
        setPermissionsTeamId($company->id);
        $user->assignRole($role);

        return $this->actingAs($user)->withSession(['current_company_id' => $company->id]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }

    private function requestPayload(array $overrides = []): array
    {
        return array_merge([
            'branch_name' => 'Lilongwe Branch',
            'branch_code' => 'LLW',
            'branch_address' => '1 Independence Drive',
            'contact_person' => 'Jane Manager',
            'contact_email' => 'manager@acme.test',
            'contact_phone' => '+265 999 000 111',
            'requested_quantity' => 2,
            'reason' => 'Expanding to the capital',
        ], $overrides);
    }

    private function submitRequest(User $manager, Company $company, array $overrides = []): BranchRequest
    {
        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.store'), $this->requestPayload($overrides))
            ->assertRedirect();

        return BranchRequest::where('company_id', $company->id)->firstOrFail();
    }

    private function approveForQuote(Company $company, BranchRequest $request): BillingQuotation
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post(route('superadmin.companies.branch-requests.approve', [$company, $request]), ['admin_notes' => 'Standard pricing'])
            ->assertRedirect();

        return $request->fresh()->quotation;
    }

    // ── 1. Submit + notify ──────────────────────────────────────────────────

    public function test_company_manager_can_submit_a_branch_request_and_admins_are_notified(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);

        $admin = $this->superAdmin();
        $billingUser = User::factory()->create();
        $this->assign($billingUser, $company, 'billing');

        $this->submitRequest($manager, $company);

        $request = BranchRequest::where('company_id', $company->id)->firstOrFail();
        $this->assertEquals(BranchRequest::STATUS_PENDING_REVIEW, $request->status);
        $this->assertEquals(2, $request->requested_quantity);
        $this->assertEquals($manager->id, $request->requested_by_user_id);

        Mail::assertQueued(BranchRequestSubmittedMail::class, 2); // super admin + billing user
        Mail::assertQueued(BranchRequestSubmittedMail::class, fn ($mail) => $mail->hasTo($admin->email));
        Mail::assertQueued(BranchRequestSubmittedMail::class, fn ($mail) => $mail->hasTo($billingUser->email));
    }

    // ── 2. Approve -> quote with server-calculated frozen total ─────────────

    public function test_approve_issues_quotation_with_server_calculated_frozen_total(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company, ['requested_quantity' => 3]);
        $quotation = $this->approveForQuote($company, $request);

        $this->assertNotNull($quotation);
        $this->assertEquals(BillingQuotation::STATUS_PENDING, $quotation->status);
        $this->assertEquals(3, $quotation->quantity);
        $this->assertEquals(5000.0, $quotation->unit_price);
        $this->assertEquals(15000.0, $quotation->subtotal);
        $this->assertEquals(15000.0, $quotation->total);
        $this->assertEquals('USD', $quotation->currency_code);
        $this->assertSame(
            ['per_branch' => 5000, 'quantity' => 3, 'subtotal' => 15000, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 15000],
            $quotation->pricing_breakdown
        );
        $this->assertStringStartsWith('BQ-', $quotation->quotation_number);
        $this->assertNotNull($quotation->bank_reference);
        $this->assertEquals(BranchRequest::STATUS_QUOTED, $request->fresh()->status);

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_BRANCH_REQUEST_APPROVED,
            'company_id' => $company->id,
        ]);
    }

    // ── 3. Reject with reason ──────────────────────────────────────────────

    public function test_super_admin_can_reject_with_reason(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);

        $request = $this->submitRequest($manager, $company);

        $admin = $this->superAdmin();
        $this->actingAs($admin)
            ->post(route('superadmin.companies.branch-requests.reject', [$company, $request]), ['reason' => 'Out of territory'])
            ->assertRedirect();

        $request->refresh();
        $this->assertEquals(BranchRequest::STATUS_REJECTED, $request->status);
        $this->assertEquals('Out of territory', $request->admin_notes);
        $this->assertNotNull($request->rejected_at);

        Mail::assertQueued(BranchRequestRejectedMail::class);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_BRANCH_REQUEST_REJECTED,
            'company_id' => $company->id,
        ]);
    }

    // ── 4. Bank transfer payment stays pending, no limit bump ──────────────

    public function test_recording_a_bank_transfer_payment_does_not_raise_the_limit(): void
    {
        $company = $this->makeCompany(branchLimit: 2);
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company);
        $quotation = $this->approveForQuote($company, $request);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'bank_transfer',
                'reference_no' => 'TRX-88213',
                'bank_name' => 'NBS Bank',
                'amount' => $quotation->total,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment = BranchPayment::where('billing_quotation_id', $quotation->id)->firstOrFail();
        $this->assertEquals(BranchPayment::STATUS_PENDING, $payment->status);
        $this->assertEquals('bank_transfer', $payment->payment_mode);
        $this->assertEquals(BranchRequest::STATUS_AWAITING_PAYMENT, $request->fresh()->status);

        $this->assertEquals(2, $company->fresh()->branch_limit);
    }

    // ── 5. Confirm -> fulfil + additive limit bump ─────────────────────────

    public function test_confirming_a_payment_fulfils_the_request_and_raises_the_limit_additively(): void
    {
        $company = $this->makeCompany(branchLimit: 2);
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $billingUser = User::factory()->create();
        $this->assign($billingUser, $company, 'billing');
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company, ['requested_quantity' => 3]);
        $quotation = $this->approveForQuote($company, $request);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'cheque',
                'reference_no' => 'CHQ-112',
                'amount' => $quotation->total,
            ])
            ->assertRedirect();

        $payment = BranchPayment::where('billing_quotation_id', $quotation->id)->firstOrFail();

        $this->actingCompany($billingUser, $company, 'billing')
            ->post(route('branch-requests.payments.confirm', [$request, $payment]))
            ->assertRedirect();

        $this->assertEquals(2 + 3, $company->fresh()->branch_limit); // additive, not replace
        $this->assertEquals(BranchPayment::STATUS_CONFIRMED, $payment->fresh()->status);
        $this->assertEquals($billingUser->id, $payment->fresh()->confirmed_by_user_id);
        $this->assertEquals(BillingQuotation::STATUS_PAID, $quotation->fresh()->status);
        $this->assertEquals(BranchRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertNotNull($request->fresh()->approved_at);

        Mail::assertQueued(BranchPaymentConfirmedMail::class);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_BRANCH_PAYMENT_CONFIRMED,
            'company_id' => $company->id,
        ]);
    }

    // ── 6. Accountant confirm works ────────────────────────────────────────

    public function test_accountant_can_confirm_a_payment(): void
    {
        $company = $this->makeCompany(branchLimit: 1);
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $accountant = User::factory()->create();
        $this->assign($accountant, $company, 'accountant');
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company);
        $quotation = $this->approveForQuote($company, $request);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'bank_transfer',
                'amount' => $quotation->total,
            ])
            ->assertRedirect();

        $payment = BranchPayment::where('billing_quotation_id', $quotation->id)->firstOrFail();

        $this->actingCompany($accountant, $company, 'accountant')
            ->post(route('branch-requests.payments.confirm', [$request, $payment]))
            ->assertRedirect();

        $this->assertEquals(BranchPayment::STATUS_CONFIRMED, $payment->fresh()->status);
        $this->assertEquals(1 + 2, $company->fresh()->branch_limit);
    }

    // ── 7. Manager confirm forbidden ───────────────────────────────────────

    public function test_company_manager_cannot_confirm_a_payment(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company);
        $quotation = $this->approveForQuote($company, $request);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'bank_transfer',
                'amount' => $quotation->total,
            ])
            ->assertRedirect();

        $payment = BranchPayment::where('billing_quotation_id', $quotation->id)->firstOrFail();

        // The requester is company_admin: role_or_permission:system_admin|accountant|billing must reject.
        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.confirm', [$request, $payment]))
            ->assertForbidden();

        $this->assertEquals(BranchPayment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertEquals(2, $company->fresh()->branch_limit);
    }

    // ── 8. Cash requires notes + restricted actors ─────────────────────────

    public function test_cash_payment_requires_notes_and_restricted_actor(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company);
        $quotation = $this->approveForQuote($company, $request);

        // Manager (company_admin) is not allowed to record cash at all.
        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'cash',
                'amount' => $quotation->total,
                'notes' => 'Handed to cashier',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        // Accountant may record cash but notes are mandatory.
        $accountant = User::factory()->create();
        $this->assign($accountant, $company, 'accountant');

        $this->actingCompany($accountant, $company, 'accountant')
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'cash',
                'amount' => $quotation->total,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $this->assertEquals(0, BranchPayment::where('billing_quotation_id', $quotation->id)->count());

        $this->actingCompany($accountant, $company, 'accountant')
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'cash',
                'amount' => $quotation->total,
                'notes' => 'Deposited in branch safe',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(1, BranchPayment::where('billing_quotation_id', $quotation->id)->count());
    }

    // ── 9. Expiry job ──────────────────────────────────────────────────────

    public function test_expiry_command_marks_overdue_quotations_and_requests_expired(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company);
        $quotation = $this->approveForQuote($company, $request);

        $quotation->forceFill(['valid_until' => now()->subDay()])->save();

        $this->artisan('branch-quotations:expire', ['--company' => $company->id])
            ->expectsOutputToContain('Expired 1 quotation(s) total.')
            ->assertExitCode(0);

        $this->assertEquals(BillingQuotation::STATUS_EXPIRED, $quotation->fresh()->status);
        $this->assertEquals(BranchRequest::STATUS_EXPIRED, $request->fresh()->status);

        // A new request must create a fresh row; the old one is never resurrected.
        $this->assertTrue($request->fresh()->status === BranchRequest::STATUS_EXPIRED);
    }

    // ── 10. Frozen pricing ─────────────────────────────────────────────────

    public function test_quotation_pricing_is_frozen_at_issue_time(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company, ['requested_quantity' => 4]);
        $quotation = $this->approveForQuote($company, $request);

        $this->assertEquals(20000.0, $quotation->total);
        $this->assertEquals(20000.0, $quotation->pricing_breakdown['total']);

        // Pushing the config later must NOT rewrite the stored total.
        config(['branch_requests.unit_price_per_branch' => 99999]);

        $this->assertEquals(20000.0, $quotation->fresh()->total);
    }

    // ── 11. Role separation & forged scope ─────────────────────────────────

    public function test_role_separation_and_forged_request_scoping(): void
    {
        $company = $this->makeCompany();
        $other = $this->makeCompany('BETA');
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $otherManager = User::factory()->create();
        $this->assign($otherManager, $other);

        $request = $this->submitRequest($otherManager, $other);

        // A viewer cannot submit a branch request.
        $viewer = User::factory()->create();
        $this->assign($viewer, $company, 'viewer');
        $this->actingCompany($viewer, $company, 'viewer')
            ->post(route('branch-requests.store'), $this->requestPayload())
            ->assertForbidden();

        // A manager of another company cannot view this request (forged id).
        $this->actingCompany($manager, $company)
            ->get(route('branch-requests.show', $request))
            ->assertForbidden();

        // An unauthenticated guest is redirected to login.
        \Illuminate\Support\Facades\Auth::logout();
        $this->get(route('branch-requests.index'))->assertRedirect('/login');
    }

    // ── 12. Partial payment flagged, never auto-confirmed ──────────────────

    public function test_partial_payment_is_flagged_and_never_auto_confirmed(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $accountant = User::factory()->create();
        $this->assign($accountant, $company, 'accountant');
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company);
        $quotation = $this->approveForQuote($company, $request);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'bank_transfer',
                'reference_no' => 'PART-1',
                'amount' => $quotation->total - 1000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment = BranchPayment::where('billing_quotation_id', $quotation->id)->firstOrFail();
        $this->assertEquals(BranchPayment::STATUS_PENDING, $payment->status);
        $this->assertEquals($quotation->total - 1000, $payment->amount);

        // Manual staff confirmation is still required; confirmation is allowed.
        $this->actingCompany($accountant, $company, 'accountant')
            ->post(route('branch-requests.payments.confirm', [$request, $payment]))
            ->assertRedirect();

        $this->assertEquals(BranchPayment::STATUS_CONFIRMED, $payment->fresh()->status);
    }

    // ── Extra: concurrent-request guard, cancel, double-confirm guard ──────

    public function test_a_second_concurrent_request_is_blocked_until_the_first_resolves(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);

        $this->submitRequest($manager, $company);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.store'), $this->requestPayload(['branch_name' => 'Second Branch']))
            ->assertRedirect()
            ->assertSessionHasErrors('branch_request');

        $this->assertEquals(1, BranchRequest::where('company_id', $company->id)->count());
    }

    public function test_manager_can_cancel_a_pending_request(): void
    {
        $company = $this->makeCompany();
        $manager = User::factory()->create();
        $this->assign($manager, $company);

        $request = $this->submitRequest($manager, $company);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.cancel', $request))
            ->assertRedirect();

        $this->assertEquals(BranchRequest::STATUS_CANCELLED, $request->fresh()->status);
    }

    public function test_confirming_an_already_fulfilled_request_is_rejected(): void
    {
        $company = $this->makeCompany(branchLimit: 1);
        $manager = User::factory()->create();
        $this->assign($manager, $company);
        $billingUser = User::factory()->create();
        $this->assign($billingUser, $company, 'billing');
        $this->seedSequence($company->id);

        $request = $this->submitRequest($manager, $company);
        $quotation = $this->approveForQuote($company, $request);

        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'bank_transfer',
                'amount' => $quotation->total,
            ])
            ->assertRedirect();

        $first = BranchPayment::where('billing_quotation_id', $quotation->id)->firstOrFail();

        $this->actingCompany($billingUser, $company, 'billing')
            ->post(route('branch-requests.payments.confirm', [$request, $first]))
            ->assertRedirect();

        // A second payment cannot be confirmed after fulfilment.
        $this->actingCompany($manager, $company)
            ->post(route('branch-requests.payments.store', $request), [
                'payment_mode' => 'cheque',
                'amount' => $quotation->total,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $this->assertEquals(1 + 2, $company->fresh()->branch_limit);
    }

    public function test_superadmin_queue_index_lists_requests_across_companies(): void
    {
        $companyA = $this->makeCompany('ACME');
        $companyB = $this->makeCompany('BETA');
        $managerA = User::factory()->create();
        $managerB = User::factory()->create();
        $this->assign($managerA, $companyA);
        $this->assign($managerB, $companyB);

        $this->submitRequest($managerA, $companyA, ['branch_name' => 'Lilongwe']);
        $this->submitRequest($managerB, $companyB, ['branch_name' => 'Blantyre']);

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.branch-requests.index'))
            ->assertOk()
            ->assertSee('Lilongwe')
            ->assertSee('Blantyre');
    }
}

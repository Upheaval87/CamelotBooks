<x-app-layout>

    <x-superadmin.layout>
        <div class="space-y-6">
            

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif

            @php
                $requestBadge = match ($request->status) {
                    \App\Models\BranchRequest::STATUS_PENDING_REVIEW => 'pending',
                    \App\Models\BranchRequest::STATUS_QUOTED => 'accent',
                    \App\Models\BranchRequest::STATUS_AWAITING_PAYMENT => 'pending',
                    \App\Models\BranchRequest::STATUS_APPROVED => 'approved',
                    \App\Models\BranchRequest::STATUS_REJECTED => 'rejected',
                    \App\Models\BranchRequest::STATUS_EXPIRED => 'neutral',
                    \App\Models\BranchRequest::STATUS_CANCELLED => 'neutral',
                    default => 'neutral',
                };
                $isDecided = in_array($request->status, [
                    \App\Models\BranchRequest::STATUS_APPROVED,
                    \App\Models\BranchRequest::STATUS_REJECTED,
                    \App\Models\BranchRequest::STATUS_EXPIRED,
                    \App\Models\BranchRequest::STATUS_CANCELLED,
                ], true);
            @endphp

            <x-review.head
                :title="$request->branch_name"
                :back-url="route('superadmin.companies.show', $company)"
                back-label="{{ __('Back to Company') }}"
            >
                <x-slot name="badge">
                    <x-review.badge :variant="$requestBadge" :dot="$request->status === \App\Models\BranchRequest::STATUS_APPROVED || $request->status === \App\Models\BranchRequest::STATUS_REJECTED">{{ $request->statusLabel() }}</x-review.badge>
                </x-slot>
            </x-review.head>

            <x-review.card title="{{ __('Branch Request') }}" icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'/>">
                <div class="mt-[22px] grid grid-cols-1 gap-x-8 gap-y-[22px] md:grid-cols-2 lg:grid-cols-3">
                    <x-review.field label="Company">{{ $company->name }}</x-review.field>
                    <x-review.field label="Branch Code" mono>{{ $request->branch_code ?? '—' }}</x-review.field>
                    <x-review.field label="Quantity Requested">{{ $request->requested_quantity }}</x-review.field>
                    <x-review.field label="Requested At">{{ $request->requested_at?->format('M j, Y g:i A') }}</x-review.field>
                    <x-review.field label="Address">{{ $request->branch_address ?? '—' }}</x-review.field>
                    <x-review.field label="Contact Person">{{ $request->contact_person ?? '—' }}</x-review.field>
                    <x-review.field label="Contact Email">{{ $request->contact_email ?? '—' }}</x-review.field>
                    <x-review.field label="Contact Phone">{{ $request->contact_phone ?? '—' }}</x-review.field>
                    <x-review.field label="Reason" class="lg:col-span-2">{{ $request->reason ?? '—' }}</x-review.field>
                </div>
            </x-review.card>

            @if(in_array($request->status, [\App\Models\BranchRequest::STATUS_PENDING_REVIEW], true))
                <x-review.decision title="{{ __('Review & Decide') }}" hint="{{ __('Approve to issue a priced quotation, or reject the request.') }}">
                    <x-slot name="fields">
                        <form id="branch-request-approve-form" method="POST" action="{{ route('superadmin.companies.branch-requests.approve', [$company, $request]) }}">
                            @csrf
                            <div>
                                <x-input-label for="admin_notes" value="{{ __('Admin Notes (optional)') }}" />
                                <textarea id="admin_notes" name="admin_notes" rows="3" class="min-h-[110px] mt-1 block w-full rounded-xl border-shell bg-white/80 focus:border-[rgba(18,143,142,.55)] focus:ring-[3px] focus:ring-[rgba(18,143,142,.15)] focus:outline-none" placeholder="{{ __('e.g. Pricing notes, agreed discounts, volume tier.') }}"></textarea>
                            </div>
                        </form>

                        <form id="branch-request-reject-form" method="POST" action="{{ route('superadmin.companies.branch-requests.reject', [$company, $request]) }}">
                            @csrf
                            <div>
                                <x-input-label for="reason" value="{{ __('Rejection Reason (required)') }}" />
                                <textarea id="reason" name="reason" rows="3" class="min-h-[110px] mt-1 block w-full rounded-xl border-shell bg-white/80 focus:border-[rgba(18,143,142,.55)] focus:ring-[3px] focus:ring-[rgba(18,143,142,.15)] focus:outline-none" required></textarea>
                                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            </div>
                        </form>
                    </x-slot>
                    <x-slot name="actions">
                        <x-review.btn variant="reject" type="submit" form="branch-request-reject-form">{{ __('Reject Request') }}</x-review.btn>
                        <x-review.btn variant="primary" size="lg" type="submit" form="branch-request-approve-form">{{ __('Approve & Issue Quotation') }}</x-review.btn>
                    </x-slot>
                </x-review.decision>
            @elseif($isDecided)
                @php
                    $outcomeTone = $request->status === \App\Models\BranchRequest::STATUS_APPROVED ? 'approved' : 'rejected';
                    $outcomeTitle = $request->status === \App\Models\BranchRequest::STATUS_APPROVED
                        ? __('Branch request approved')
                        : __('Branch request not approved');
                    $outcomeDescription = $request->status === \App\Models\BranchRequest::STATUS_APPROVED
                        ? ($request->admin_notes ?: __('The requested branch capacity was granted.'))
                        : ($request->admin_notes ?: __('No additional branch capacity was granted.'));
                @endphp
                <x-review.outcome
                    :title="$outcomeTitle"
                    :description="$outcomeDescription"
                    chip="{{ strtoupper($request->statusLabel()) }}"
                    :tone="$outcomeTone"
                />
            @endif

            @if($request->quotation)
                @php $quotation = $request->quotation; @endphp
                <x-review.card title="{{ __('Quotation') }} {{ $quotation->quotation_number }}">
                    <x-slot name="action">
                        @php
                            $qStatus = match ($quotation->status) {
                                'paid' => 'approved',
                                'expired', 'cancelled' => 'neutral',
                                default => 'pending',
                            };
                        @endphp
                        <x-review.badge :variant="$qStatus">{{ $quotation->statusLabel() }}</x-review.badge>
                    </x-slot>

                    <div class="mt-[22px] grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="kpi-card">
                            <div class="kpi-label">Unit Price ({{ $quotation->currency_code }})</div>
                            <div class="kpi-value">{{ number_format($quotation->unit_price, 2) }}</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Quantity</div>
                            <div class="kpi-value">{{ $quotation->quantity }}</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Total</div>
                            <div class="kpi-value">{{ number_format($quotation->total, 2) }} {{ $quotation->currency_code }}</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Valid Until</div>
                            <div class="kpi-value">{{ $quotation->valid_until?->format('M j, Y') }}</div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-shell bg-white/80 px-4 py-3 text-sm">
                        <span class="font-medium text-gray-900">Bank Reference:</span>
                        <code class="ml-1 font-mono text-xs text-gray-600">{{ $quotation->bank_reference }}</code>
                    </div>

                    @if($quotation->payments->isNotEmpty())
                        <div class="mt-6">
                            <div class="form-section-label mb-3">Payments</div>
                            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                                <table class="w-full min-w-[720px] border-collapse text-sm">
                                    <thead>
                                        <tr>
                                            <x-superadmin.th>{{ __('Mode') }}</x-superadmin.th>
                                            <x-superadmin.th>{{ __('Reference') }}</x-superadmin.th>
                                            <x-superadmin.th align="right">{{ __('Amount') }}</x-superadmin.th>
                                            <x-superadmin.th>{{ __('Date') }}</x-superadmin.th>
                                            <x-superadmin.th align="center">{{ __('Status') }}</x-superadmin.th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line">
                                        @foreach($quotation->payments as $payment)
                                            <tr>
                                                <td class="px-5 py-[18px] align-middle text-gray-600">{{ $payment->modeLabel() }}</td>
                                                <td class="px-5 py-[18px] align-middle text-gray-600">{{ $payment->reference_no ?? '—' }}</td>
                                                <td class="px-5 py-[18px] text-right align-middle font-semibold tabular-nums text-gray-900">{{ number_format($payment->amount, 2) }} {{ $quotation->currency_code }}</td>
                                                <td class="px-5 py-[18px] align-middle text-gray-500">{{ $payment->paid_at?->format('M j, Y') }}</td>
                                                <td class="px-5 py-[18px] text-center align-middle">
                                                    @php
                                                        $pVariant = $payment->status === 'confirmed' ? 'approved' : ($payment->status === 'rejected' ? 'rejected' : 'pending');
                                                    @endphp
                                                    <x-review.badge :variant="$pVariant">{{ $payment->statusLabel() }}</x-review.badge>
                                                    @if($payment->amount !== round((float) $quotation->total, 2) && $payment->status === 'pending')
                                                        <span class="mt-1 block text-xs text-gold">{{ __('Amount differs from total') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </x-review.card>
            @endif
        </div>
    </x-superadmin.layout>
</x-app-layout>

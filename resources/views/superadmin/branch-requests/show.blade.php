<x-app-layout>

    <x-superadmin.layout>
        <div class="space-y-6">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif

            @php
                $requestBadge = match ($request->status) {
                    \App\Models\BranchRequest::STATUS_PENDING_REVIEW => 'warning',
                    \App\Models\BranchRequest::STATUS_QUOTED => 'accent',
                    \App\Models\BranchRequest::STATUS_AWAITING_PAYMENT => 'warning',
                    \App\Models\BranchRequest::STATUS_APPROVED => 'active',
                    \App\Models\BranchRequest::STATUS_REJECTED => 'danger',
                    \App\Models\BranchRequest::STATUS_EXPIRED => 'muted',
                    \App\Models\BranchRequest::STATUS_CANCELLED => 'muted',
                    default => 'muted',
                };
            @endphp

            <x-superadmin.card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-[26px] font-extrabold tracking-[-0.02em] text-gray-900">{{ $request->branch_name }}</span>
                        <x-superadmin.badge :variant="$requestBadge">{{ $request->statusLabel() }}</x-superadmin.badge>
                    </div>
                    <x-superadmin.btn variant="ghost" href="{{ route('superadmin.companies.show', $company) }}">{{ __('Back to Company') }}</x-superadmin.btn>
                </div>

                <div class="detail-grid mt-6">
                    <x-detail-field label="Company">{{ $company->name }}</x-detail-field>
                    <x-detail-field label="Branch Code">{{ $request->branch_code ?? '—' }}</x-detail-field>
                    <x-detail-field label="Quantity Requested">{{ $request->requested_quantity }}</x-detail-field>
                    <x-detail-field label="Requested At">{{ $request->requested_at?->format('M j, Y g:i A') }}</x-detail-field>
                    <x-detail-field label="Address">{{ $request->branch_address ?? '—' }}</x-detail-field>
                    <x-detail-field label="Contact Person">{{ $request->contact_person ?? '—' }}</x-detail-field>
                    <x-detail-field label="Contact Email">{{ $request->contact_email ?? '—' }}</x-detail-field>
                    <x-detail-field label="Contact Phone">{{ $request->contact_phone ?? '—' }}</x-detail-field>
                    <x-detail-field label="Reason">{{ $request->reason ?? '—' }}</x-detail-field>
                </div>
            </x-superadmin.card>

            @if(in_array($request->status, [\App\Models\BranchRequest::STATUS_PENDING_REVIEW], true))
                <x-superadmin.card title="{{ __('Review & Decide') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <form method="POST" action="{{ route('superadmin.companies.branch-requests.approve', [$company, $request]) }}">
                            @csrf
                            <div class="mb-3">
                                <x-input-label for="admin_notes" value="{{ __('Admin Notes (optional)') }}" />
                                <textarea id="admin_notes" name="admin_notes" rows="3" class="input mt-1 block w-full" placeholder="{{ __('e.g. Pricing notes, agreed discounts, volume tier.') }}"></textarea>
                            </div>
                            <x-superadmin.btn type="submit">{{ __('Approve & Issue Quotation') }}</x-superadmin.btn>
                        </form>

                        <form method="POST" action="{{ route('superadmin.companies.branch-requests.reject', [$company, $request]) }}">
                            @csrf
                            <div class="mb-3">
                                <x-input-label for="reason" value="{{ __('Rejection Reason (required)') }}" />
                                <textarea id="reason" name="reason" rows="3" class="input mt-1 block w-full" required></textarea>
                                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            </div>
                            <x-superadmin.btn variant="danger" type="submit">{{ __('Reject Request') }}</x-superadmin.btn>
                        </form>
                    </div>
                </x-superadmin.card>
            @endif

            @if($request->quotation)
                @php $quotation = $request->quotation; @endphp
                <x-superadmin.card title="{{ __('Quotation') }} {{ $quotation->quotation_number }}">
                    <x-slot name="action">
                        @php
                            $qStatus = match ($quotation->status) {
                                'paid' => 'active',
                                'expired', 'cancelled' => 'muted',
                                default => 'warning',
                            };
                        @endphp
                        <x-superadmin.badge :variant="$qStatus">{{ $quotation->statusLabel() }}</x-superadmin.badge>
                    </x-slot>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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

                    <div class="mt-4 rounded-lg border border-line bg-panel px-4 py-3 text-sm">
                        <span class="font-medium text-ink">Bank Reference:</span>
                        <code class="ml-1 font-mono text-xs">{{ $quotation->bank_reference }}</code>
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
                                                        $pVariant = $payment->status === 'confirmed' ? 'active' : ($payment->status === 'rejected' ? 'danger' : 'warning');
                                                    @endphp
                                                    <x-superadmin.badge :variant="$pVariant">{{ $payment->statusLabel() }}</x-superadmin.badge>
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
                </x-superadmin.card>
            @endif
        </div>
    </x-superadmin.layout>
</x-app-layout>

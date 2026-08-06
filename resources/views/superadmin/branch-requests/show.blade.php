<x-app-layout>
    <x-slot name="header">{{ __('Branch Request') }} - {{ $request->branch_name }}</x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ $errors->first() }}</div>
            @endif

            <div class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-semibold text-ink">{{ $request->branch_name }}</span>
                        @include('branch-requests._status', ['status' => $request->status])
                    </div>
                    <a href="{{ route('superadmin.companies.show', $company) }}" class="btn-ghost">{{ __('Back to Company') }}</a>
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
            </div>

            @if(in_array($request->status, [\App\Models\BranchRequest::STATUS_PENDING_REVIEW], true))
                <div class="card p-6">
                    <div class="form-section-label mb-4">Review & Decide</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <form method="POST" action="{{ route('superadmin.companies.branch-requests.approve', [$company, $request]) }}">
                            @csrf
                            <div class="mb-3">
                                <x-input-label for="admin_notes" value="{{ __('Admin Notes (optional)') }}" />
                                <textarea id="admin_notes" name="admin_notes" rows="3" class="input mt-1 block w-full" placeholder="{{ __('e.g. Pricing notes, agreed discounts, volume tier.') }}"></textarea>
                            </div>
                            <x-button variant="primary" type="submit">{{ __('Approve & Issue Quotation') }}</x-button>
                        </form>

                        <form method="POST" action="{{ route('superadmin.companies.branch-requests.reject', [$company, $request]) }}">
                            @csrf
                            <div class="mb-3">
                                <x-input-label for="reason" value="{{ __('Rejection Reason (required)') }}" />
                                <textarea id="reason" name="reason" rows="3" class="input mt-1 block w-full" required></textarea>
                                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            </div>
                            <x-button variant="danger" type="submit">{{ __('Reject Request') }}</x-button>
                        </form>
                    </div>
                </div>
            @endif

            @if($request->quotation)
                @php $quotation = $request->quotation; @endphp
                <div class="card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h3 class="text-sm font-semibold text-ink">Quotation {{ $quotation->quotation_number }}</h3>
                        <div class="flex items-center gap-2">
                            @php
                                $qStatus = match ($quotation->status) {
                                    'paid' => 'positive',
                                    'expired', 'cancelled' => 'default',
                                    default => 'neutral',
                                };
                            @endphp
                            <span class="status-pill {{ $qStatus }}">{{ $quotation->statusLabel() }}</span>
                        </div>
                    </div>

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
                            <div class="datasheet-wrap">
                                <div class="overflow-x-auto">
                                    <table class="datasheet">
                                        <thead>
                                            <tr>
                                                <th>Mode</th>
                                                <th>Reference</th>
                                                <th class="text-right">Amount</th>
                                                <th>Date</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($quotation->payments as $payment)
                                                <tr>
                                                    <td>{{ $payment->modeLabel() }}</td>
                                                    <td>{{ $payment->reference_no ?? '—' }}</td>
                                                    <td class="text-right font-medium text-ink">{{ number_format($payment->amount, 2) }} {{ $quotation->currency_code }}</td>
                                                    <td>{{ $payment->paid_at?->format('M j, Y') }}</td>
                                                    <td class="text-center">
                                                        @php
                                                            $pStatus = $payment->status === 'confirmed' ? 'positive' : ($payment->status === 'rejected' ? 'negative' : 'neutral');
                                                        @endphp
                                                        <span class="status-pill {{ $pStatus }}">{{ $payment->statusLabel() }}</span>
                                                        @if($payment->amount !== round((float) $quotation->total, 2) && $payment->status === 'pending')
                                                            <span class="block text-xs text-gold mt-1">Amount differs from total</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

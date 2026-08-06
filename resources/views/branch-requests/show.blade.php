<x-app-layout>
    <x-slot name="header">{{ __('Branch Request') }} - {{ $branchRequest->branch_name }}</x-slot>

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
                        <span class="text-lg font-semibold text-ink">{{ $branchRequest->branch_name }}</span>
                        @include('branch-requests._status', ['status' => $branchRequest->status])
                    </div>
                    @if(in_array($branchRequest->status, [\App\Models\BranchRequest::STATUS_PENDING_REVIEW, \App\Models\BranchRequest::STATUS_QUOTED], true))
                        <form method="POST" action="{{ route('branch-requests.cancel', $branchRequest) }}" onsubmit="return confirm('{{ __('Cancel this branch request?') }}')">
                            @csrf
                            <x-button variant="danger" type="submit">{{ __('Cancel Request') }}</x-button>
                        </form>
                    @endif
                </div>

                <div class="detail-grid mt-6">
                    <x-detail-field label="Branch Code">{{ $branchRequest->branch_code ?? '—' }}</x-detail-field>
                    <x-detail-field label="Quantity Requested">{{ $branchRequest->requested_quantity }}</x-detail-field>
                    <x-detail-field label="Requested At">{{ $branchRequest->requested_at?->format('M j, Y g:i A') }}</x-detail-field>
                    <x-detail-field label="Address">{{ $branchRequest->branch_address ?? '—' }}</x-detail-field>
                    <x-detail-field label="Contact Person">{{ $branchRequest->contact_person ?? '—' }}</x-detail-field>
                    <x-detail-field label="Contact Email">{{ $branchRequest->contact_email ?? '—' }}</x-detail-field>
                    <x-detail-field label="Contact Phone">{{ $branchRequest->contact_phone ?? '—' }}</x-detail-field>
                    <x-detail-field label="Reason">{{ $branchRequest->reason ?? '—' }}</x-detail-field>
                    <x-detail-field label="Admin Notes">{{ $branchRequest->admin_notes ?? '—' }}</x-detail-field>
                </div>
            </div>

            @if($branchRequest->quotation)
                @php $quotation = $branchRequest->quotation; @endphp
                <div class="card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Quotation') }} {{ $quotation->quotation_number }}</h3>
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
                        <span class="font-medium text-ink">{{ __('Bank Reference:') }}</span>
                        <code class="ml-1 font-mono text-xs">{{ $quotation->bank_reference }}</code>
                        <span class="ml-2 text-ink-soft">{{ __('Use this reference when making your payment.') }}</span>
                    </div>

                    @if(in_array($branchRequest->status, [\App\Models\BranchRequest::STATUS_QUOTED, \App\Models\BranchRequest::STATUS_AWAITING_PAYMENT], true))
                        <div class="mt-6">
                            <div class="form-section-label mb-3">Record Offline Payment</div>
                            <form method="POST" action="{{ route('branch-requests.payments.store', $branchRequest) }}">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <x-input-label for="payment_mode" value="{{ __('Payment Mode') }}" />
                                        <select id="payment_mode" name="payment_mode" class="input mt-1 block w-full" required>
                                            <option value="bank_transfer" {{ old('payment_mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="cheque" {{ old('payment_mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                            <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="amount" value="{{ __('Amount') }}" />
                                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="input mt-1 block w-full" :value="old('amount', $quotation->total)" required />
                                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="paid_at" value="{{ __('Payment Date') }}" />
                                        <x-text-input id="paid_at" name="paid_at" type="date" class="input mt-1 block w-full" :value="old('paid_at', now()->toDateString())" />
                                    </div>
                                    <div>
                                        <x-input-label for="reference_no" value="{{ __('Reference / Cheque No') }}" />
                                        <x-text-input id="reference_no" name="reference_no" type="text" class="input mt-1 block w-full" :value="old('reference_no')" />
                                    </div>
                                    <div>
                                        <x-input-label for="bank_name" value="{{ __('Bank Name') }}" />
                                        <x-text-input id="bank_name" name="bank_name" type="text" class="input mt-1 block w-full" :value="old('bank_name')" />
                                    </div>
                                    <div>
                                        <x-input-label for="notes" value="{{ __('Notes') }}" />
                                        <x-text-input id="notes" name="notes" type="text" class="input mt-1 block w-full" :value="old('notes')" />
                                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                                    </div>
                                </div>
                                @if(!$canConfirmPayment)
                                    <p class="mt-3 text-xs text-ink-soft">
                                        {{ __('Cash payments require a billing or accounting user to record them. Other payments are recorded here and confirmed by billing staff.') }}
                                    </p>
                                @endif
                                <div class="mt-4 flex justify-end">
                                    <x-primary-button type="submit">{{ __('Record Payment') }}</x-primary-button>
                                </div>
                            </form>
                        </div>
                    @endif

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
                                                <th class="text-right">Actions</th>
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
                                                            <span class="block text-xs text-gold mt-1">{{ __('Amount differs from total') }}</span>
                                                        @endif
                                                        @if($payment->rejection_reason)
                                                            <span class="block text-xs text-brick mt-1">{{ $payment->rejection_reason }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right whitespace-nowrap">
                                                        @if($payment->status === 'pending' && $canConfirmPayment)
                                                            <form method="POST" action="{{ route('branch-requests.payments.confirm', [$branchRequest, $payment]) }}" class="inline" onsubmit="return confirm('{{ __('Confirm this payment and raise the branch limit?') }}')">
                                                                @csrf
                                                                <button type="submit" class="px-3 py-1 text-xs text-green-600 hover:text-green-800">{{ __('Confirm') }}</button>
                                                            </form>
                                                            <button type="button" class="px-3 py-1 text-xs text-red-600 hover:text-red-800" onclick="document.getElementById('reject-{{ $payment->id }}').classList.toggle('hidden')">{{ __('Reject') }}</button>
                                                            <form id="reject-{{ $payment->id }}" method="POST" action="{{ route('branch-requests.payments.reject', [$branchRequest, $payment]) }}" class="hidden mt-1">
                                                                @csrf
                                                                <input type="text" name="reason" placeholder="Rejection reason" required class="input mt-1 block w-full text-xs" />
                                                                <button type="submit" class="mt-1 text-xs text-red-600">{{ __('Confirm Rejection') }}</button>
                                                            </form>
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

            @if($branchRequest->status === \App\Models\BranchRequest::STATUS_APPROVED)
                <div class="rounded-lg border border-forest bg-forest-soft px-4 py-3 text-sm text-forest">
                    {{ __('This request is fulfilled. Your branch limit has been raised — you can now create the branch from the Branches screen.') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

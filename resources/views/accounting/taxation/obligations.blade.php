<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Obligations') }}</h1>
                <p class="sub">{{ __('One row per open obligation across all tax types — reconcile, file and pay each period in order.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('accounting.taxation.reconciliation') }}" class="tx-btn tx-btn-cta">{{ __('Run Reconciliation') }}</a>
            </div>
        </div>

        <div class="tx-kpis" style="grid-template-columns:repeat(4, 1fr);">
            <div class="tx-kpi">
                <div class="l">{{ __('Open Obligations') }}</div>
                <div class="v">{{ $totalCount }}</div>
                <div class="n">{{ __('across every tax type') }}</div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('In Progress') }}</div>
                <div class="v">{{ $openCount }}</div>
                <div class="n">{{ __('not yet ready to reconcile') }}</div>
            </div>
            <div class="tx-kpi {{ $readyCount > 0 ? '' : '' }}">
                <div class="l">{{ __('Ready to Reconcile') }}</div>
                <div class="v">{{ $readyCount }}</div>
                <div class="n">{{ __('awaiting reconciliation sign-off') }}</div>
            </div>
            <div class="tx-kpi {{ $overdueCount > 0 ? 'warn' : '' }}">
                <div class="l">{{ __('Overdue') }}</div>
                <div class="v">{{ $overdueCount }}</div>
                <div class="n">{{ __('past the filing due date') }}</div>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:980px;">
                    <thead>
                        <tr>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Filing Due') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Remarks') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $tchipClass = match ($row['category']) {
                                    'WHT' => 'tx-t-wht',
                                    'PAYE' => 'tx-t-paye',
                                    'FBT' => 'tx-t-fbt',
                                    default => 'tx-t-vat',
                                };
                                $statusMap = [
                                    \App\Models\TaxObligation::STATUS_OPEN => ['tx-b-off', __('Awaiting Transactions')],
                                    \App\Models\TaxObligation::STATUS_CALCULATING => ['tx-b-pend', __('Calculating')],
                                    \App\Models\TaxObligation::STATUS_READY_TO_RECONCILE => ['tx-b-pend', __('Ready to Reconcile')],
                                    \App\Models\TaxObligation::STATUS_RECONCILED => ['tx-b-post', __('Reconciled')],
                                    \App\Models\TaxObligation::STATUS_RETURN_DRAFTED => ['tx-b-post', __('Return Drafted')],
                                    \App\Models\TaxObligation::STATUS_RETURN_APPROVED => ['tx-b-post', __('Return Approved')],
                                    \App\Models\TaxObligation::STATUS_FILED => ['tx-b-post', __('Filed')],
                                    \App\Models\TaxObligation::STATUS_PAID => ['tx-b-ok', __('Paid')],
                                    \App\Models\TaxObligation::STATUS_REJECTED => ['tx-b-rev', __('Rejected')],
                                ];
                                [$badgeClass, $badgeLabel] = $statusMap[$row['status']] ?? ['tx-b-off', $row['status']];

                                $remarks = [];
                                if ($row['is_overdue']) {
                                    $remarks[] = __('Overdue — past {due}', ['due' => $row['filing_due_date'] ?? '']);
                                }
                                if (! empty($row['blocked_reason'])) {
                                    $remarks[] = $row['blocked_reason'];
                                }
                                if ($row['variance_waived']) {
                                    $remarks[] = __('Variance waived');
                                }
                                if ($row['nil_or_refund_flag']) {
                                    $remarks[] = __('Nil / refund declared');
                                }
                            @endphp
                            <tr>
                                <td class="tx-name">{{ $row['period_label'] }}</td>
                                <td><span class="tx-tchip {{ $tchipClass }}">{{ $row['tax_type'] ?? $row['tax_type_code'] }}</span></td>
                                <td>{{ $row['filing_due_date'] ?? '&mdash;' }}</td>
                                <td>
                                    <span class="tx-badge {{ $badgeClass }}"><span class="bdot"></span>{{ $badgeLabel }}</span>
                                    @if ($row['is_overdue'])
                                        <span class="tx-badge tx-b-rev"><span class="bdot"></span>{{ __('Overdue') }}</span>
                                    @endif
                                </td>
                                <td class="tx-em" style="color:var(--muted);">
                                    @if ($remarks)
                                        {{ implode(' · ', $remarks) }}
                                    @else
                                        <span style="color:var(--faint);">&mdash;</span>
                                    @endif
                                </td>
                                <td class="tx-row-act">
                                    @if ($row['status'] === \App\Models\TaxObligation::STATUS_READY_TO_RECONCILE)
                                        <form method="POST"
                                              action="{{ route('accounting.taxation.obligations.reconcile', ['period' => $row['period_id']]) }}"
                                              style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                                            @csrf
                                            <input type="checkbox" name="waive" value="1" id="waive-{{ $row['id'] }}">
                                            <label for="waive-{{ $row['id'] }}" style="font-size:12px;color:var(--muted);">{{ __('Waive variance') }}</label>
                                            <input type="text" name="waive_reason" placeholder="{{ __('Reason (required to waive)') }}" style="max-width:170px;" class="tx-input">
                                            <button type="submit" class="tx-btn tx-btn-sm tx-btn-cta">{{ __('Reconcile') }}</button>
                                        </form>
                                    @endif

                                    @if ($row['status'] === \App\Models\TaxObligation::STATUS_REJECTED)
                                        <form method="POST"
                                              action="{{ route('accounting.taxation.obligations.reopen', ['period' => $row['period_id']]) }}"
                                              style="display:flex;gap:6px;align-items:center;justify-content:flex-end;">
                                            @csrf
                                            <button type="submit" class="tx-btn tx-btn-sm">{{ __('Reopen') }}</button>
                                        </form>
                                    @endif

                                    <a class="tx-jl" href="{{ $row['working_paper_url'] }}">Working paper &rarr;</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No open tax obligations — post a transaction to create the first one.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tx-note">
            {{ __('Obligations progress in order: Calculating &rarr; Ready to Reconcile &rarr; Reconciled &rarr; Return Drafted &rarr; Return Approved &rarr; Filed &rarr; Paid &rarr; Closed. A non-zero variance must be waived with a reason before reconciliation.') }}
        </div>
    </div>
</x-app-layout>
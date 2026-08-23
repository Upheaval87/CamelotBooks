<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Audit Trail') }}</h1>
                <p class="sub">{{ __('Every change to tax configuration and filings, with before/after values.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="tx-btn tx-btn-ghost" onclick="window.txExportTable(this, 'tax-audit-trail')">Export</button>
            </div>
        </div>

        @php
            $entityKindLabels = [
                'tax_code' => __('Tax code'),
                'tax_rate' => __('Tax rate'),
                'tax_period' => __('Tax period'),
                'tax_return' => __('Tax return'),
                'tax_adjustment' => __('Adjustment'),
                'tax_payment' => __('Payment'),
                'wht_certificate' => __('WHT certificate'),
                'recognition_rule' => __('Recognition rule'),
                'apportionment_rule' => __('Apportionment rule'),
                'tax_transaction' => __('Transaction'),
            ];
            $approvalMap = [
                'PENDING' => ['tx-b-pend', __('Pending')],
                'APPROVED' => ['tx-b-ok', __('Approved')],
                'REJECTED' => ['tx-b-rev', __('Rejected')],
                null => ['tx-b-off', __('System')],
            ];
        @endphp

        <div class="tx-card">
            <form method="GET" action="{{ route('accounting.taxation.audit-trail') }}" class="tx-f">
                <select name="entity_kind" class="tx-ddl">
                    <option value="">{{ __('All entities') }}</option>
                    @foreach ($entityKindLabels as $kind => $label)
                        <option value="{{ $kind }}" @selected(request('entity_kind') === $kind)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="tx-inp-sm" title="{{ __('From date') }}">
                <input type="date" name="to" value="{{ request('to') }}" class="tx-inp-sm" title="{{ __('To date') }}">
                <button type="submit" class="tx-btn tx-btn-sec">{{ __('Filter') }}</button>
                <a href="{{ route('accounting.taxation.audit-trail') }}" class="tx-btn tx-btn-ghost">{{ __('Clear') }}</a>
            </form>

            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:980px;">
                    <thead>
                        <tr>
                            <th>{{ __('Date & Time') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Entity') }}</th>
                            <th>{{ __('Change') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Approval') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php [$apprClass, $apprLabel] = $approvalMap[$log->approval] ?? ['tx-b-off', ucfirst(strtolower((string) $log->approval))];
                            @endphp
                            <tr>
                                <td>{{ $log->acted_at->format('d M Y') }}<br><span class="tx-em">{{ $log->acted_at->format('H:i') }}</span></td>
                                <td class="tx-name">{{ $log->user?->name ?? __('System') }}</td>
                                <td><span class="tx-mono tx-em">{{ $entityKindLabels[$log->entity_kind] ?? Str::of($log->entity_kind)->replace('_', ' ')->title() }}</span></td>
                                <td>
                                    @if ($log->field)
                                        {{ $log->field }}: <span class="tx-em">&ldquo;{{ Str::limit($log->old_value, 40) }}&rdquo;</span> &rarr; <strong>&ldquo;{{ Str::limit($log->new_value, 40) }}&rdquo;</strong>
                                    @else
                                        <span class="tx-em">{{ __('Created / updated') }}</span> <strong>&ldquo;{{ Str::limit($log->new_value, 60) }}&rdquo;</strong>
                                    @endif
                                </td>
                                <td class="tx-em">{{ Str::limit($log->reason, 70) }}</td>
                                <td><span class="tx-badge {{ $apprClass }}"><span class="bdot"></span>{{ $apprLabel }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">No audit entries match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tx-pag">
                <div class="info">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }} &middot; {{ $logs->total() }} entries</div>
                <div style="display:flex;gap:6px;">
                    @if ($logs->previousPageUrl())
                        <a href="{{ $logs->previousPageUrl() }}" class="tx-btn tx-btn-ghost tx-btn-sm">&larr; Prev</a>
                    @endif
                    @if ($logs->nextPageUrl())
                        <a href="{{ $logs->nextPageUrl() }}" class="tx-btn tx-btn-ghost tx-btn-sm">Next &rarr;</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    @php
        $cs = $currencySymbol ?? '$';
        $drillBranch = (int)($branchId ?? 0) ? '&branch_id='.$branchId : '';
    @endphp

    <div class="fr-wrap">
        <div class="fr-head">
            <div>
                <h1>{{ __('A/R Aging Detail') }}</h1>
                <div class="fr-sub">As at {{ \Carbon\Carbon::parse($as_of_date)->format('d M Y') }} · {{ $cs }}</div>
            </div>
            <div class="fr-actions">
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" onclick="window.print()">Print</button>
                <a href="{{ route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ar'])) }}" class="fr-btn fr-btn-ghost fr-btn-sm">Excel</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.aging.ar-detail') }}" class="fr-filters">
            <div class="fr-f">
                <label for="as_of_date">{{ __('As Of Date') }}</label>
                <input type="date" id="as_of_date" name="as_of_date" value="{{ $as_of_date }}">
            </div>
            <div class="fr-f">
                <label for="branch_id">{{ __('Branch') }}</label>
                <x-scoped-search-field
                    name="branch_id"
                    entity="branch"
                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                    :value="request('branch_id')"
                    :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                    placeholder="{{ __('All Branches') }}"
                />
            </div>
            <div style="display:flex;gap:.5rem">
                <button type="submit" class="fr-btn fr-btn-cta fr-btn-sm">Generate</button>
                <a href="{{ route('accounting.aging.ar-detail') }}" class="fr-btn fr-btn-ghost fr-btn-sm">Clear</a>
            </div>
        </form>

        <div class="fr-tabs">
            <a href="{{ route('accounting.aging.ar-summary', request()->query()) }}" class="fr-tab">Summary</a>
            <a href="{{ route('accounting.aging.ar-detail', request()->query()) }}" class="fr-tab active">Detail</a>
        </div>

        <div class="fr-card">
            <div class="fr-card-head">
                <h2>A/R Aging Detail</h2>
                <div style="margin-left:auto;font-size:.75rem;color:var(--muted,#5f7476)">{{ count($customers) }} invoice{{ count($customers) !== 1 ? 's' : '' }}</div>
            </div>
            <div class="fr-table-wrap">
                <table class="fr-table">
                    <thead>
                        <tr>
                            <th style="width:22%">Customer</th>
                            <th style="width:14%">Invoice #</th>
                            <th class="r" style="width:14%">Due Date</th>
                            <th class="r" style="width:14%">Days Overdue</th>
                            <th class="r" style="width:18%">Amount Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.customers.show', $row['customer_id']).'?'.$drillBranch }}" class="fr-tl">{{ $row['customer_name'] }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('accounting.invoices.show', $row['invoice_id']).'?'.$drillBranch }}" class="fr-tl" style="font-weight:500">{{ $row['invoice_number'] ?? '-' }}</a>
                                </td>
                                <td class="r">{{ $row['due_date'] ? \Carbon\Carbon::parse($row['due_date'])->format('d M Y') : '-' }}</td>
                                <td class="r">
                                    @php $days = $row['days_overdue'] ?? 0; @endphp
                                    @if($days > 90)
                                        <span class="fr-badge fr-badge-red"><span class="fr-badge-dot"></span>{{ $days }}d</span>
                                    @elseif($days > 30)
                                        <span class="fr-badge fr-badge-amber"><span class="fr-badge-dot"></span>{{ $days }}d</span>
                                    @elseif($days > 0)
                                        <span class="fr-badge">{{ $days }}d</span>
                                    @else
                                        {{ $days }}
                                    @endif
                                </td>
                                <td class="r" style="font-weight:700">{{ format_number($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:2rem 1rem;color:var(--faint,#8aa5a7)">No outstanding invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;font-weight:800">Total</td>
                            <td class="r" style="font-weight:800">{{ format_number($totals['total']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="fr-actionbar">
            <a href="{{ route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ar'])) }}" class="fr-btn fr-btn-ghost">Export CSV</a>
            <button type="button" class="fr-btn fr-btn-cta" onclick="window.print()">Print / PDF</button>
        </div>
    </div>
</x-app-layout>

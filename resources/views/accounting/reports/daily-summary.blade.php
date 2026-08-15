<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    <x-list-header title="{{ __('Sales Receipts Daily Summary') }}" description="{{ __('Posted sales receipts grouped by day for the selected period.') }}" />

    <form method="GET" class="q2-card q2-filters mt-4">
        <div class="q2-field">
            <x-input-label for="date_from" value="{{ __('From') }}" class="q2-label" />
            <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" class="q2-input" />
        </div>
        <div class="q2-field">
            <x-input-label for="date_to" value="{{ __('To') }}" class="q2-label" />
            <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" class="q2-input" />
        </div>
        <div class="q2-filters-actions">
            <button type="submit" class="q2-btn q2-btn--cta">{{ __('Apply') }}</button>
            <a href="{{ route('accounting.reports.sales-receipts.daily-summary') }}" class="q2-btn q2-btn--ghost">{{ __('Reset') }}</a>
        </div>
    </form>

    <div class="q2-statgrid mt-4">
        <div class="q2-stat">
            <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <div class="q2-stat-meta">
                <span class="q2-stat-lbl">{{ __('Total Receipts') }}</span>
                <span class="q2-stat-val">{{ number_format($receipt_count) }}</span>
                <span class="q2-stat-var">{{ __('Posted') }} · {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }} – {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') }}</span>
            </div>
        </div>
        <div class="q2-stat">
            <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <div class="q2-stat-meta">
                <span class="q2-stat-lbl">{{ __('Total Value') }}</span>
                <span class="q2-stat-val">{{ format_number($total) }}</span>
                <span class="q2-stat-var">{{ $cs }}</span>
            </div>
        </div>
    </div>

    <div class="q2-card q2-card--list mt-4">
        @if(count($rows) > 0)
            <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                <table class="q2-tbl">
                    <thead><tr>
                        <th>{{ __('Date') }}</th>
                        <th class="q2-right">{{ __('Receipts') }}</th>
                        <th class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                    </tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                                <td class="q2-right">{{ number_format($row['count']) }}</td>
                                <td class="q2-right q2-amt" style="font-weight:800">{{ format_number($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="q2-lbl">{{ __('Total') }}</td>
                            <td class="q2-right">{{ number_format($receipt_count) }}</td>
                            <td class="figure q2-right" style="font-weight:800;color:var(--deep-3,#0A2E32)">{{ format_number($total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="q2-empty">{{ __('No posted sales receipts in this period.') }}</div>
        @endif
    </div>
</div>
</x-app-layout>

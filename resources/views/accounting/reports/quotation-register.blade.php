<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    <x-list-header title="{{ __('Quotation Register') }}" description="{{ __('Every quotation for a period with totals and status.') }}" />

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
            <a href="{{ route('accounting.reports.quotation-register') }}" class="q2-btn q2-btn--ghost">{{ __('Reset') }}</a>
            <a href="{{ route('accounting.quotations.index') }}" class="q2-btn q2-btn--ghost">{{ __('Open Quotations') }}</a>
        </div>
    </form>

    <div class="q2-card q2-card--list mt-4">
        <div class="q2-tbl-wrap" style="border:none;border-radius:16px">
            <table class="q2-tbl">
                <thead><tr>
                    <th class="q2-mono">{{ __('Quotation №') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Valid Until') }}</th>
                    <th class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                    <th>{{ __('Status') }}</th>
                </tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="q2-mono">{{ $row['quotation_number'] }}</td>
                            <td style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $row['customer_name'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['valid_until'] ?? '—' }}</td>
                            <td class="q2-right q2-amt">{{ format_number($row['total']) }}</td>
                            <td>
                                <span class="q2-badge q2-badge--{{ $row['status'] }}"><span class="q2-dot"></span>
                                    @switch($row['status'])
                                        @case('draft') {{ __('Draft') }} @break
                                        @case('sent') {{ __('Sent') }} @break
                                        @case('accepted') {{ __('Accepted') }} @break
                                        @case('declined') {{ __('Declined') }} @break
                                        @case('converted') {{ __('Converted') }} @break
                                        @case('void') {{ __('Void') }} @break
                                    @endswitch
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="q2-empty">{{ __('No quotations in this period.') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="q2-lbl">{{ __('Total') }}</td>
                        <td class="q2-right q2-amt">{{ format_number($total) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if(!empty($rows))
        <div class="mt-4 flex justify-end">
            <a href="{{ route('accounting.quotations.index') }}" target="_blank" class="q2-btn q2-btn--ghost">{{ __('Print') }}</a>
        </div>
    @endif
</div>
</x-app-layout>

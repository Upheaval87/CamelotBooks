<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="ex-suite wrap">
        <div class="page-head">
            <div>
                <h1>{{ __('All Expenses') }}</h1>
                <div class="sub">{{ __('Every business expense across branches, departments and cost centres.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <details class="more">
                    <summary class="btn btn-ghost btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M6 9h12M6 15h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {{ __('More') }}
                    </summary>
                    <div class="more-menu">
                        <a class="more-item" href="{{ route('accounting.expenses.reports') }}">{{ __('Reports') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.recurring.index') }}">{{ __('Recurring Expenses') }}</a>
                    </div>
                </details>
                <a href="{{ route('accounting.expenses.create') }}" class="btn btn-cta btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('Record Expense') }}
                </a>
            </div>
        </div>

        @include('accounting.expenses._list')
    </div>
</x-app-layout>

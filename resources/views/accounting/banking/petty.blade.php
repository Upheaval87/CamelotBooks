<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $funds = collect($summary);
        $totalBalance = $funds->sum(fn ($f) => (float) ($f['current_balance'] ?? 0));
        $totalSpent = $funds->sum(fn ($f) => (float) ($f['spent'] ?? 0));
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Petty Cash') }}</h1>
                    <p class="q2-sub">{{ __('Petty cash funds, expenses and replenishments.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.petty.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('New Fund') }}</a>
                </div>
            </div>

            <div class="q2-fbox-grid q2-fbox-grid--4">
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Fund Balance') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $cs }}{{ format_number($totalBalance) }}</span>
                </div>
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--red"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Spent') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $cs }}{{ format_number($totalSpent) }}</span>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Petty Cash Funds') }}</h2>
                        </div>
                        <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                            <table class="q2-tbl">
                                <thead>
                                    <tr>
                                        <th style="width:14%">{{ __('Code') }}</th>
                                        <th style="width:30%">{{ __('Fund') }}</th>
                                        <th style="width:18%" class="q2-right">{{ __('Balance') }} ({{ $cs }})</th>
                                        <th style="width:24%">{{ __('Status') }}</th>
                                        <th style="width:14%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($funds as $fund)
                                        <tr>
                                            <td class="q2-mono">{{ $fund['code'] }}</td>
                                            <td class="q2-amt" style="font-weight:600;color:var(--deep-3,#0A2E32)">
                                                <a href="{{ route('accounting.banking.petty.show', $fund['id']) }}" class="q2-link">{{ $fund['name'] }}</a>
                                            </td>
                                            <td class="q2-right q2-amt">{{ format_number($fund['current_balance']) }}</td>
                                            <td>
                                                <span class="q2-badge q2-badge--accepted"><span class="q2-dot"></span>{{ __('Active') }}</span>
                                            </td>
                                            <td class="q2-right">
                                                <a href="{{ route('accounting.banking.petty.show', $fund['id']) }}" class="q2-ibtn" title="{{ __('View') }}">
                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2"/><path d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"><div class="q2-empty">{{ __('No petty cash funds yet.') }}</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.dashboard') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Banking Centre') }}</a>
                            <a href="{{ route('accounting.banking.accounts') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('Bank Accounts') }}</a>
                            <a href="{{ route('accounting.banking.petty') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Petty Cash') }}</a>
                        </div>
                        <div class="q2-rule"></div>
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Reports') }}</div>
                            <a href="{{ route('accounting.banking.reports') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 17v-5M13 17V7M17 17v-2M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Reports') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

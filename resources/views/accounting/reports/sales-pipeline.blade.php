<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    <x-list-header title="{{ __('Sales Pipeline') }}" description="{{ __('Quotation funnel by status with win-rate and open-quote aging.') }}" />

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
            <a href="{{ route('accounting.reports.sales-pipeline') }}" class="q2-btn q2-btn--ghost">{{ __('Reset') }}</a>
        </div>
    </form>

    <div class="q2-statgrid mt-4">
        <div class="q2-stat">
            <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <div class="q2-stat-meta">
                <span class="q2-stat-lbl">{{ __('Open Value') }}</span>
                <span class="q2-stat-val">{{ format_number($open_value) }}</span>
                <span class="q2-stat-var">{{ $cs }}</span>
            </div>
        </div>
        <div class="q2-stat">
            <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <div class="q2-stat-meta">
                <span class="q2-stat-lbl">{{ __('Win Rate') }}</span>
                <span class="q2-stat-val">{{ number_format($win_rate, 1) }}%</span>
                <span class="q2-stat-var">{{ __('Accepted + Converted ÷ Decided') }}</span>
            </div>
        </div>
        <div class="q2-stat">
            <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <div class="q2-stat-meta">
                <span class="q2-stat-lbl">{{ __('Open Quotes') }}</span>
                <span class="q2-stat-val">{{ number_format($open_count) }}</span>
                <span class="q2-stat-var">{{ __('Draft + Sent + Accepted') }}</span>
            </div>
        </div>
        <div class="q2-stat">
            <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <div class="q2-stat-meta">
                <span class="q2-stat-lbl">{{ __('Accepted Value') }}</span>
                <span class="q2-stat-val">{{ format_number($accepted_value) }}</span>
                <span class="q2-stat-var">{{ $cs }} · {{ number_format($accepted_count) }} {{ __('quotes') }}</span>
            </div>
        </div>
        <div class="q2-stat">
            <span class="q2-stat-ic q2-stat-ic--red"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <div class="q2-stat-meta">
                <span class="q2-stat-lbl">{{ __('Total Value') }}</span>
                <span class="q2-stat-val">{{ format_number($total_value) }}</span>
                <span class="q2-stat-var">{{ $cs }} · {{ number_format($total) }} {{ __('quotes') }}</span>
            </div>
        </div>
    </div>

    <div class="q2-sec mt-4">
        <div class="q2-sec-head">
            <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <h2 class="q2-sec-title">{{ __('Pipeline by Status') }}</h2>
            <span class="q2-sec-rule"></span>
        </div>
        <div class="q2-pipe-body">
            @foreach($stages as $key => $stage)
                <div class="q2-pipe-row">
                    <span class="q2-pipe-ic q2-pipe-ic--{{ $stage['icon'] }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg></span>
                    <span class="q2-pipe-label">{{ $stage['label'] }}</span>
                    <span class="q2-pipe-bar"><i style="width: {{ $stage['pct'] }}%"></i></span>
                    <span class="q2-pipe-count">{{ number_format($stage['count']) }}</span>
                    <span class="q2-pipe-val">{{ $cs }} {{ format_number($stage['value']) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="q2-sec mt-4">
        <div class="q2-sec-head">
            <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <h2 class="q2-sec-title">{{ __('Open Quote Aging') }}</h2>
            <span class="q2-sec-rule"></span>
        </div>
        <div class="q2-pipe-body">
            @foreach($aging as $key => $bucket)
                <div class="q2-pipe-row">
                    <span class="q2-pipe-ic q2-pipe-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg></span>
                    <span class="q2-pipe-label">{{ $bucket['label'] }}</span>
                    <span class="q2-pipe-bar"><i style="width: {{ $bucket['pct'] }}%"></i></span>
                    <span class="q2-pipe-count">{{ number_format($bucket['count']) }}</span>
                    <span class="q2-pipe-val">{{ $cs }} {{ format_number($bucket['value']) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
</x-app-layout>

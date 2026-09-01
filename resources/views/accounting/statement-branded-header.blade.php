{{--
    Branded header / meta / footer for the 5 financial statements.
    Hidden on screen (display:none); restored in @media print / PDF.

    Required props: $company (Company model), $title (string)
    Optional props: $periodLabel, $currency, $cs, $dp, $basis, $preparedBy, $preparedAt, $branchLine
--}}
@php
    $companyName = $company->name ?? 'Company';
    $logoPath    = $company->logo ?? null;
    $hasLogo     = $logoPath && file_exists(public_path('storage/' . $logoPath));
    $initials    = strtoupper(mb_substr(trim($companyName), 0, 2));
    $tpin        = $company->tax_id ?? null;
    $branchLine  = $branchLine ?? null;
    $orgLine     = trim(implode(' · ', array_filter([$branchLine, $tpin ? 'TPIN ' . $tpin : null])));
    $currency    = $currency ?? $company->base_currency ?? '';
    $cs          = $cs ?? '$';
    $basis       = $basis ?? 'Accrual';
    $preparedAt  = $preparedAt ?? now();
    $preparedBy  = $preparedBy ?? '—';
    $periodLabel = $periodLabel ?? '';
@endphp

{{-- doc-h — centred lockup: logo + company on the SAME row --}}
<header class="doc-h">
    <div class="doc-h-logo">
        @if($hasLogo)
            <img src="{{ asset('storage/' . $logoPath) }}" alt="">
        @else
            <span class="doc-h-mono">{{ $initials }}</span>
        @endif
    </div>
    <div class="doc-h-id">
        <div class="doc-h-company">{{ $companyName }}</div>
        @if($orgLine)
            <div class="doc-h-orgline">{{ $orgLine }}</div>
        @endif
    </div>
</header>
<hr class="doc-h-rule">
<div class="doc-h-title">{{ $title }}</div>
@if($periodLabel)
    <div class="doc-h-period">{{ $periodLabel }}</div>
@endif

{{-- meta — 4-cell hairline strip --}}
<div class="meta">
    <div class="meta-cell">
        <div class="meta-label">{{ __('Currency') }}</div>
        <div class="meta-value">{{ $currency }} ({{ $cs }})</div>
    </div>
    <div class="meta-cell">
        <div class="meta-label">{{ __('Basis') }}</div>
        <div class="meta-value">{{ $basis }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-label">{{ __('Prepared') }}</div>
        <div class="meta-value">{{ \Carbon\Carbon::parse($preparedAt)->format('d M Y') }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-label">{{ __('Prepared By') }}</div>
        <div class="meta-value">{{ $preparedBy }}</div>
    </div>
</div>

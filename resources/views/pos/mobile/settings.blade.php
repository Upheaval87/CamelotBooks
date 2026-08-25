@extends('layouts.pos-mobile', ['title' => 'Settings'])

@section('content')
<div class="pos-m-page" style="padding-bottom: 5rem;">

    {{-- §13 — Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Settings</div>
        <div class="pos-m-greeting-sub">Store · devices · account</div>
    </div>

    {{-- §13 — Profile --}}
    <div class="pos-m-settings-profile">
        <div class="pos-m-settings-avatar">
            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
        </div>
        <div>
            <div class="pos-m-settings-name">{{ $user->name ?? 'User' }}</div>
            <div class="pos-m-settings-sub">
                {{ $company?->name ?? 'Store' }}
                @if($terminal?->branch) · {{ $terminal->branch->name }} @endif
            </div>
        </div>
    </div>

    {{-- §13 — Store details --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Store</div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                <span>Store details</span>
                <span class="pos-m-settings-row-sub">Address · contact · logo</span>
            </div>
            <svg class="pos-m-settings-row-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                <span>Tax & currency</span>
                <span class="pos-m-settings-row-sub">{{ $company?->base_currency ?? 'USD' }} · VAT</span>
            </div>
            <svg class="pos-m-settings-row-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                <span>Receipt footer</span>
                <span class="pos-m-settings-row-sub">Thank-you note · return policy</span>
            </div>
            <svg class="pos-m-settings-row-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>
    </div>

    {{-- §13 — Devices --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Devices</div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                <span>Receipt printer</span>
                <span class="pos-m-settings-row-sub">Not paired</span>
            </div>
            <span class="pos-m-settings-dot pos-m-settings-dot--gray"></span>
        </div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                <span>Barcode scanner</span>
                <span class="pos-m-settings-row-sub">Not paired</span>
            </div>
            <span class="pos-m-settings-dot pos-m-settings-dot--gray"></span>
        </div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                <span>Cash drawer</span>
                <span class="pos-m-settings-row-sub">Not connected</span>
            </div>
            <span class="pos-m-settings-dot pos-m-settings-dot--gray"></span>
        </div>
    </div>

    {{-- §13 — Preferences --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Preferences</div>
        <div class="pos-m-settings-row" x-data="{ dark: false }">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">Dark mode</div>
            <button class="pos-m-toggle" :class="dark ? 'pos-m-toggle--on' : ''" @click="dark = !dark">
                <span class="pos-m-toggle-thumb"></span>
            </button>
        </div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">Language</div>
            <span class="pos-m-settings-value">English</span>
        </div>
    </div>

    {{-- §13 — Account --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Account</div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">Users & roles</div>
            <svg class="pos-m-settings-row-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>
        <div class="pos-m-settings-row pos-m-settings-row--danger" onclick="document.getElementById('pos-logout-form').submit()">
            <div class="pos-m-settings-row-icon pos-m-settings-row-icon--danger">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </div>
            <div class="pos-m-settings-row-text">Sign out</div>
        </div>
    </div>

    <form id="pos-logout-form" method="POST" action="{{ route('pos.logout') }}" style="display:none;">
        @csrf
    </form>

</div>

@include('pos.mobile._bottom-nav', ['active' => 'more'])
@endsection

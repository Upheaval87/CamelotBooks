@extends('layouts.pos-mobile', ['title' => 'Settings'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5.5rem">

    {{-- Profile --}}
    <div class="pos-m-settings-profile">
        <div class="pos-m-settings-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        <div>
            <div class="pos-m-settings-name">{{ $user->name }}</div>
            <div class="pos-m-settings-sub">{{ $company?->name ?? 'CamelotBooks' }}</div>
        </div>
    </div>

    {{-- Register info --}}
    @if($terminal)
    <div class="pos-m-section-card">
        <div class="pos-m-section-head">
            <div class="pos-m-section-icon pos-m-section-icon--solid">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div class="pos-m-section-title">Current Register</div>
                <div class="pos-m-section-sub">{{ $terminal->identifier }} · {{ $terminal->name }}</div>
            </div>
        </div>
        <div class="pos-m-kv-grid">
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Branch</span>
                <span class="pos-m-kv-v" style="font-size:.875rem">{{ $terminal->branch?->name ?? '—' }}</span>
            </div>
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Status</span>
                <span class="pos-m-kv-v" style="font-size:.875rem;color:#1B7F4D">Active</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Store --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Store</div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                Store
                <span class="pos-m-settings-row-sub">{{ $company?->name ?? 'CamelotBooks' }}</span>
            </div>
        </div>
    </div>

    {{-- Devices --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Devices</div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                Terminal
                <span class="pos-m-settings-row-sub">{{ $terminal?->identifier ?? 'None' }}</span>
            </div>
        </div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                Printer
                <span class="pos-m-settings-row-sub">Thermal 80mm</span>
            </div>
        </div>
    </div>

    {{-- Preferences --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Preferences</div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                Currency
                <span class="pos-m-settings-row-sub">{{ $company->base_currency ?? 'MWK' }}</span>
            </div>
        </div>
        <div class="pos-m-settings-row">
            <div class="pos-m-settings-row-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="pos-m-settings-row-text">
                Receipt Format
                <span class="pos-m-settings-row-sub">Thermal 80mm</span>
            </div>
        </div>
    </div>

    {{-- Account --}}
    <div class="pos-m-settings-group">
        <div class="pos-m-settings-group-title">Account</div>
        <form id="pos-logout-form" method="POST" action="{{ route('pos.logout') }}">
            @csrf
            <button type="submit" class="pos-m-settings-row pos-m-settings-row--danger" style="width:100%;border:none;background:none;text-align:left;cursor:pointer">
                <div class="pos-m-settings-row-icon pos-m-settings-row-icon--danger">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </div>
                <div class="pos-m-settings-row-text" style="color:#C2453F">Sign out</div>
            </button>
        </form>
    </div>

    @include('pos.mobile._bottom-nav', ['active' => 'more'])
</div>
@endsection

{{--
    §8 shared slim-rail + quick-link drawer for the Vendor Centre suite.
    Usage: @include('accounting.vendors._slim-rail', ['active' => 'dashboard'])
    $active: dashboard | vendors | bills | payments | credits | reports | settings
    The content wrapper must carry class="stage" so the pinned drawer can shift it.
--}}
@props(['active' => ''])

<div class="slim-rail" id="vc-rail" role="navigation" aria-label="{{ __('Vendor Centre quick links') }}">
    <button type="button" class="s-ic @if($active === 'dashboard') on @endif" data-tip="{{ __('Dashboard') }}" data-sec="dashboard" aria-label="{{ __('Dashboard') }}" onclick="VcRail.toggle(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-7h-6v7H4a1 1 0 01-1-1V9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <a href="{{ route('accounting.vendors.index') }}" class="s-ic @if($active === 'vendors') on @endif" data-tip="{{ __('Vendors') }}" aria-label="{{ __('Vendors') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a href="{{ route('accounting.bills.index') }}" class="s-ic @if($active === 'bills') on @endif" data-tip="{{ __('Bills') }}" aria-label="{{ __('Bills') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 2h12a1 1 0 011 1v19l-3-2-3 2-3-2-3 2V3a1 1 0 011-1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7h6M9 11h6M9 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </a>
    <a href="{{ route('accounting.vendor-payments.index') }}" class="s-ic @if($active === 'payments') on @endif" data-tip="{{ __('Payments') }}" aria-label="{{ __('Payments') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a href="{{ route('accounting.vendor-credits.index') }}" class="s-ic @if($active === 'credits') on @endif" data-tip="{{ __('Credits') }}" aria-label="{{ __('Credits') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a href="{{ route('accounting.vendors.reports') }}" class="s-ic @if($active === 'reports') on @endif" data-tip="{{ __('Reports') }}" aria-label="{{ __('Reports') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a href="{{ route('accounting.vendors.settings') }}" class="s-ic @if($active === 'settings') on @endif" data-tip="{{ __('Settings') }}" aria-label="{{ __('Settings') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
</div>

<div class="ql-drawer" id="vc-drawer" aria-hidden="true">
    @php
        $groups = [
            'dashboard' => [
                ['label' => __('Vendor Dashboard'), 'icon' => 'M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-7h-6v7H4a1 1 0 01-1-1V9.5z', 'url' => route('accounting.vendors.dashboard')],
                ['label' => __('All Vendors'), 'icon' => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2', 'url' => route('accounting.vendors.index')],
                ['label' => __('New Vendor'), 'icon' => 'M12 5v14m-7-7h14', 'url' => route('accounting.vendors.create')],
            ],
            'vendors' => [
                ['label' => __('All Vendors'), 'icon' => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2', 'url' => route('accounting.vendors.index')],
                ['label' => __('New Vendor'), 'icon' => 'M12 5v14m-7-7h14', 'url' => route('accounting.vendors.create')],
                ['label' => __('Vendor Dashboard'), 'icon' => 'M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-7h-6v7H4a1 1 0 01-1-1V9.5z', 'url' => route('accounting.vendors.dashboard')],
            ],
            'bills' => [
                ['label' => __('All Bills'), 'icon' => 'M6 2h12a1 1 0 011 1v19l-3-2-3 2-3-2-3 2V3a1 1 0 011-1z', 'url' => route('accounting.bills.index')],
                ['label' => __('New Bill'), 'icon' => 'M12 5v14m-7-7h14', 'url' => route('accounting.bills.create')],
                ['label' => __('Pending Approval'), 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'url' => route('accounting.bills.index', ['status' => 'pending_approval'])],
                ['label' => __('Overdue'), 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'url' => route('accounting.bills.index', ['status' => 'overdue'])],
            ],
            'payments' => [
                ['label' => __('All Payments'), 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8', 'url' => route('accounting.vendor-payments.index')],
                ['label' => __('New Payment'), 'icon' => 'M12 5v14m-7-7h14', 'url' => route('accounting.vendor-payments.create')],
            ],
            'credits' => [
                ['label' => __('All Vendor Credits'), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'url' => route('accounting.vendor-credits.index')],
                ['label' => __('New Vendor Credit'), 'icon' => 'M12 5v14m-7-7h14', 'url' => route('accounting.vendor-credits.create')],
            ],
            'reports' => [
                ['label' => __('Vendor Reports'), 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z', 'url' => route('accounting.vendors.reports')],
                ['label' => __('AP Aging Summary'), 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z', 'url' => route('accounting.aging.ap-summary')],
                ['label' => __('AP Aging Detail'), 'icon' => 'M3 4h18v16H3z', 'url' => route('accounting.aging.ap-detail')],
                ['label' => __('Purchases by Vendor'), 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z', 'url' => route('accounting.reports.purchases-by-vendor')],
                ['label' => __('Vendor Statement'), 'icon' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z', 'url' => route('accounting.reports.vendor-statement')],
            ],
            'settings' => [
                ['label' => __('Vendor Settings'), 'icon' => 'M12 15a3 3 0 100-6 3 3 0 000 6z', 'url' => route('accounting.vendors.settings')],
                ['label' => __('Vendor Dashboard'), 'icon' => 'M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-7h-6v7H4a1 1 0 01-1-1V9.5z', 'url' => route('accounting.vendors.dashboard')],
            ],
        ];
        $group = $groups[$active] ?? $groups['dashboard'];
    @endphp
    <div class="rail-block">
        <div class="rail-head">
            <h3>{{ __('Quick Links') }}</h3>
            <button type="button" class="rail-x" aria-label="{{ __('Close') }}" onclick="VcRail.close()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </div>
        @foreach($group as $link)
            <a href="{{ $link['url'] }}" class="rlink">
                <span class="ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $link['icon'] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
    <div class="rail-block">
        <div class="rail-head">
            <h3>{{ __('Vendor Centre') }}</h3>
            <button type="button" class="rail-pin @if(session('vc_rail_pinned')) on @endif" aria-label="{{ __('Pin') }}" title="{{ __('Pin rail') }}" onclick="VcRail.togglePin(this)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 17v5M5 6h14v1l-2 5h-3v5l-1 2-1-2v-5H7l-2-5V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <a href="{{ route('accounting.vendors.dashboard') }}" class="rlink">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-7h-6v7H4a1 1 0 01-1-1V9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            {{ __('Dashboard') }}
        </a>
        <a href="{{ route('accounting.vendors.index') }}" class="rlink">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            {{ __('Vendors') }}
        </a>
        <a href="{{ route('accounting.vendors.reports') }}" class="rlink">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            {{ __('Reports') }}
        </a>
        <a href="{{ route('accounting.vendors.settings') }}" class="rlink">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            {{ __('Settings') }}
        </a>
    </div>
</div>

<script>
    window.VcRail = {
        open: false,
        toggle(btn) {
            const drawer = document.getElementById('vc-drawer');
            if (btn && btn.dataset.sec && !this.open) {
                // non-nav rail buttons open the drawer for the current section
            }
            this.open = !this.open;
            drawer.classList.toggle('open', this.open);
            drawer.setAttribute('aria-hidden', this.open ? 'false' : 'true');
        },
        close() {
            const drawer = document.getElementById('vc-drawer');
            this.open = false;
            drawer.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
        },
        togglePin(btn) {
            const pinned = document.body.classList.toggle('vc-ql-pinned');
            btn.classList.toggle('on', pinned);
            if (!pinned) this.close();
            fetch('{{ route("accounting.vendors.rail-pin") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
                body: JSON.stringify({ pinned })
            }).catch(() => {});
        }
    };
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.VcRail?.open) window.VcRail.close();
    });
    (() => {
        if (document.querySelector('.rail-pin')?.classList.contains('on')) {
            document.body.classList.add('vc-ql-pinned');
        }
    })();
</script>

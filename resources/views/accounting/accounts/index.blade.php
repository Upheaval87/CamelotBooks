@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $search = request('search');
    $typeFilter = request('type');
@endphp

<x-app-layout>
    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
<div class="suite">

    {{-- page head --}}
    <div class="page-head">
        <div>
            <h1>{{ __('Chart of Accounts') }}</h1>
            <div class="sub">Your general ledger accounts organised by type.</div>
        </div>
        <div class="tbtns">
            <a href="{{ route('accounting.accounts.create') }}" class="btn cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('Create Account') }}
            </a>
        </div>
    </div>

    {{-- stats --}}
    <div class="sgrid">
        <div class="sbox">
            <div class="l">{{ __('Total') }}</div>
            <div class="v">{{ $stats['total'] }}</div>
        </div>
        <div class="sbox">
            <div class="l">{{ __('Active') }}</div>
            <div class="v t-teal">{{ $stats['active'] }}</div>
        </div>
        <div class="sbox">
            <div class="l">{{ __('Inactive') }}</div>
            <div class="v t-mint">{{ $stats['inactive'] }}</div>
        </div>
    </div>

    {{-- toolbar --}}
    <div class="toolbar" style="margin-top:16px">
        <form method="GET" action="{{ route('accounting.accounts.index') }}" class="controls">
            <div class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by code or name..." autocomplete="off" />
            </div>
            <select name="type" class="input" style="width:auto;min-width:170px">
                <option value="">All Types</option>
                @foreach($typeLabels as $value => $label)
                    <option value="{{ $value }}" {{ $typeFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn ghost sm">{{ __('Filter') }}</button>
            @if($search || $typeFilter)
                <a href="{{ route('accounting.accounts.index') }}" class="btn ghost sm">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    {{-- accounts grouped by type --}}
    @forelse($typeLabels as $type => $label)
        @if($grouped->has($type))
            <section class="card card-sec" style="margin-top:16px">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/></svg></span>
                    <h2>{{ $label }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table class="li-tbl">
                        <thead>
                            <tr>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Sub Type') }}</th>
                                <th class="right">{{ __('Balance') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grouped[$type]->sortBy('code') as $account)
                                <tr class="{{ $account->is_active ? '' : 'row-inact' }}">
                                    <td><a href="{{ route('accounting.accounts.show', $account) }}" class="mono row-link">{{ $account->code }}</a></td>
                                    <td><a href="{{ route('accounting.accounts.show', $account) }}" class="row-link">{{ $account->name }}</a></td>
                                    <td class="muted">{{ str_replace('_', ' ', ucfirst($account->sub_type)) }}</td>
                                    <td class="right numr">{{ format_number($account->current_balance) }}</td>
                                    <td>
                                        @if($account->is_active)
                                            <span class="badge b-act"><span class="bdot"></span>Active</span>
                                        @else
                                            <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                        @endif
                                    </td>
                                    <td class="right row-act">
                                        <a href="{{ route('accounting.accounts.edit', $account) }}" class="ibtn" title="{{ __('Edit') }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('accounting.accounts.toggle', $account) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ $account->is_active ? __('Deactivate this account?') : __('Activate this account?') }}', { type: 'danger' })">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ibtn {{ $account->is_active ? 'del' : '' }}" title="{{ $account->is_active ? __('Deactivate') : __('Activate') }}">
                                                @if($account->is_active)
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                                                @else
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                                @endif
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @foreach($accounts->where('parent_id', $account->id)->sortBy('code') as $child)
                                    <tr class="{{ $child->is_active ? '' : 'row-inact' }}">
                                        <td><a href="{{ route('accounting.accounts.show', $child) }}" class="mono row-link">{{ $child->code }}</a></td>
                                        <td>
                                            <span class="muted" style="margin-right:4px">—</span>
                                            <a href="{{ route('accounting.accounts.show', $child) }}" class="row-link">{{ $child->name }}</a>
                                        </td>
                                        <td class="muted">{{ str_replace('_', ' ', ucfirst($child->sub_type)) }}</td>
                                        <td class="right numr">{{ format_number($child->current_balance) }}</td>
                                        <td>
                                            @if($child->is_active)
                                                <span class="badge b-act"><span class="bdot"></span>Active</span>
                                            @else
                                                <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                            @endif
                                        </td>
                                        <td class="right row-act">
                                            <a href="{{ route('accounting.accounts.edit', $child) }}" class="ibtn" title="{{ __('Edit') }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </a>
                                            <form method="POST" action="{{ route('accounting.accounts.toggle', $child) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ $child->is_active ? __('Deactivate this account?') : __('Activate this account?') }}', { type: 'danger' })">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ibtn {{ $child->is_active ? 'del' : '' }}" title="{{ $child->is_active ? __('Deactivate') : __('Activate') }}">
                                                    @if($child->is_active)
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                                                    @else
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @empty
        <div class="card" style="margin-top:16px">
            <div class="empty">No accounts found.</div>
        </div>
    @endforelse

</div>
        </div>
    </div>
</x-app-layout>

@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $balance = $account->current_balance;
@endphp

<x-app-layout>
    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
<div class="suite">

    {{-- page head --}}
    <div class="page-head">
        <div>
            <a href="{{ route('accounting.accounts.index') }}" class="backlink">Chart of Accounts</a>
            <h1>{{ $account->name }}</h1>
            <div class="sub">
                <span class="mono-chip">{{ $account->code }}</span>
                <span>{{ ucfirst($account->type) }}</span>
                <span>{{ str_replace('_', ' ', ucfirst($account->sub_type)) }}</span>
                <span>{{ $account->currency }}</span>
            </div>
        </div>
        <div class="tbtns">
            <a href="{{ route('accounting.accounts.edit', $account) }}" class="btn cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                {{ __('Edit') }}
            </a>
            @if($account->is_active)
                <form method="POST" action="{{ route('accounting.accounts.toggle', $account) }}" id="account-archive-form" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Deactivate this account?') }}', { type: 'danger' })">
                    @csrf @method('PATCH')
                </form>
                <button type="submit" form="account-archive-form" class="btn danger-o sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                    {{ __('Deactivate') }}
                </button>
            @endif
        </div>
    </div>

    {{-- stats --}}
    <div class="sgrid">
        <div class="sbox">
            <div class="l">{{ __('Current Balance') }}</div>
            <div class="v">{{ $cs }} {{ format_number($balance) }}</div>
        </div>
        <div class="sbox">
            <div class="l">{{ __('Opening Balance') }}</div>
            <div class="v">{{ $cs }} {{ format_number($account->opening_balance) }}</div>
        </div>
        <div class="sbox">
            <div class="l">{{ __('Status') }}</div>
            <div class="v">
                @if($account->is_active)
                    <span class="badge b-act"><span class="bdot"></span>Active</span>
                @else
                    <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                @endif
            </div>
        </div>
    </div>

    <div class="shell">
        <div class="flex flex-col gap-5 min-w-0">

            {{-- account information --}}
            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/></svg></span>
                    <h2>Account Information</h2>
                    <span class="rule"></span>
                </div>
                <div class="g4">
                    <div class="field"><div class="label">Code</div><div class="val mono">{{ $account->code }}</div></div>
                    <div class="field"><div class="label">Name</div><div class="val">{{ $account->name }}</div></div>
                    <div class="field"><div class="label">Type</div><div class="val">{{ ucfirst($account->type) }}</div></div>
                    <div class="field"><div class="label">Sub Type</div><div class="val">{{ str_replace('_', ' ', ucfirst($account->sub_type)) }}</div></div>
                    <div class="field"><div class="label">Currency</div><div class="val">{{ $account->currency }}</div></div>
                    <div class="field"><div class="label">Status</div><div class="val">
                        @if($account->is_active)
                            <span class="badge b-act"><span class="bdot"></span>Active</span>
                        @else
                            <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                        @endif
                    </div></div>
                    @if($account->parent)
                        <div class="field"><div class="label">Parent Account</div><div class="val"><a href="{{ route('accounting.accounts.show', $account->parent) }}" class="row-link">{{ $account->parent->code }} - {{ $account->parent->name }}</a></div></div>
                    @endif
                    @if($account->description)
                        <div class="field sp3"><div class="label">Description</div><div class="val">{{ $account->description }}</div></div>
                    @endif
                </div>
            </section>

            {{-- balance --}}
            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    <h2>Balance</h2>
                    <span class="rule"></span>
                </div>
                <div class="g4">
                    <div class="field"><div class="label">Opening Balance</div><div class="val">{{ $cs }} {{ format_number($account->opening_balance) }}</div></div>
                    <div class="field"><div class="label">Opening Balance Date</div><div class="val">{{ $account->opening_balance_date?->format('M d, Y') ?? '—' }}</div></div>
                </div>
                <div class="gt-row" style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:14px;border-top:1px solid var(--line, #E2ECEC)">
                    <span class="gt-lbl" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted, #5F7476)">Current Balance</span>
                    <span class="gt-val" style="font-size:16px;font-weight:800;color:var(--ink, #0B2A2D)">{{ $cs }} {{ format_number($balance) }}</span>
                </div>
            </section>

            {{-- child accounts --}}
            @if($account->children->count() > 0)
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v6m0 0a3 3 0 0 0 6 0M3 13a3 3 0 0 1 6 0m12-6v6m-6 0a3 3 0 0 1 6 0m-6 0a3 3 0 0 0 6 0m-12 6v2m6-2v2"/></svg></span>
                        <h2>Child Accounts</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="li-wrap" style="margin-top:0">
                        <table class="li-tbl">
                            <thead>
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th class="right">{{ __('Balance') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($account->children as $child)
                                    <tr>
                                        <td><a href="{{ route('accounting.accounts.show', $child) }}" class="mono row-link">{{ $child->code }}</a></td>
                                        <td><a href="{{ route('accounting.accounts.show', $child) }}" class="row-link">{{ $child->name }}</a></td>
                                        <td class="right numr">{{ format_number($child->current_balance) }}</td>
                                        <td>
                                            @if($child->is_active)
                                                <span class="badge b-act"><span class="bdot"></span>Active</span>
                                            @else
                                                <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        {{-- right rail --}}
        <aside>
            <div class="railsum">
                <div class="card">
                    <div class="rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                            <h2>Summary</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="vlist" style="margin-top:12px">
                            <div class="srow"><span class="l">Opening Balance</span><span class="v">{{ $cs }} {{ format_number($account->opening_balance) }}</span></div>
                            <div class="srow gt"><span class="l">Current Balance</span><span class="v">{{ $cs }} {{ format_number($balance) }}</span></div>
                        </div>
                    </div>

                    <div class="rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                            <h2>Quick Nav</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="vlist">
                            <a href="{{ route('accounting.accounts.edit', $account) }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></span>
                                Edit Account
                            </a>
                            <a href="{{ route('accounting.journal-entries.create') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></span>
                                New Journal Entry
                            </a>
                            <a href="{{ route('accounting.general-ledger.index') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></span>
                                General Ledger
                            </a>
                            <a href="{{ route('accounting.trial-balance.index') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg></span>
                                Trial Balance
                            </a>
                            <a href="{{ route('accounting.accounts.index') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></span>
                                Chart of Accounts
                            </a>
                            <a href="javascript:void(0)" onclick="window.print()" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg></span>
                                Print
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
        </div>
    </div>
</x-app-layout>

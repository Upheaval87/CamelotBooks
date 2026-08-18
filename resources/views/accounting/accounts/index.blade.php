@php
    $search = request('search');
    $typeFilter = request('type');
    $statusFilter = request('status');
    $typeOrder = ['asset', 'liability', 'equity', 'income', 'expense'];
    $totalForBar = max($stats['total'], 1);
    $activePct = round(($stats['active'] / $totalForBar) * 100);
    $inactivePct = round(($stats['inactive'] / $totalForBar) * 100);
    $typeNoteParts = [];
    foreach ($typeOrder as $t) {
        if (($typeCounts[$t] ?? 0) > 0) {
            $typeNoteParts[] = ucfirst($t === 'asset' ? 'Assets' : ($t === 'liability' ? 'Liabilities' : $t)) . ' ' . $typeCounts[$t];
        }
    }
    $activeNote = $stats['active'] === $stats['total'] ? '100% of accounts in use' : $stats['active'] . ' of ' . $stats['total'] . ' in use';
    $inactiveNote = $stats['inactive'] === 0 ? 'No dormant accounts' : $stats['inactive'] . ' dormant accounts';
@endphp

<x-app-layout>
    <div class="coa-wrap" style="padding-top:24px;padding-bottom:24px">

        {{-- Page head --}}
        <div class="coa-head">
            <div>
                <h1>{{ __('Chart of Accounts') }}</h1>
                <div class="sub">Your general ledger accounts organised by type.</div>
            </div>
            <div class="coa-head-btns">
                <a href="{{ route('accounting.reports.chart-of-accounts') }}" class="coa-btn coa-btn-ghost">{{ __('Import / Export') }}</a>
                <a href="{{ route('accounting.accounts.create') }}" class="coa-btn coa-btn-cta">{{ __('＋ Create Account') }}</a>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="coa-kpis">
            <div class="coa-kpi">
                <div class="l">{{ __('Total Accounts') }}</div>
                <div class="v">{{ $stats['total'] }}</div>
                <div class="bar"><i class="coa-fill-teal" style="width:100%"></i></div>
                <div class="n">{{ implode(' · ', $typeNoteParts) ?: 'No accounts' }}</div>
            </div>
            <div class="coa-kpi">
                <div class="l">{{ __('Active') }}</div>
                <div class="v">{{ $stats['active'] }}</div>
                <div class="bar"><i class="coa-fill-teal" style="width:{{ $activePct }}%"></i></div>
                <div class="n">{{ $activeNote }}</div>
            </div>
            <div class="coa-kpi">
                <div class="l">{{ __('Inactive') }}</div>
                <div class="v">{{ $stats['inactive'] }}</div>
                <div class="bar"><i class="coa-fill-soft" style="width:{{ $inactivePct }}%"></i></div>
                <div class="n">{{ $inactiveNote }}</div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="coa-card" style="margin-bottom:16px">
            <div class="coa-pad">
                <form method="GET" action="{{ route('accounting.accounts.index') }}">
                    <div class="coa-toolbar">
                        <input class="coa-in coa-grow" name="search" placeholder="Search by code or name…" value="{{ $search }}">
                        <select class="coa-in" name="type">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="asset" {{ $typeFilter === 'asset' ? 'selected' : '' }}>Asset</option>
                            <option value="liability" {{ $typeFilter === 'liability' ? 'selected' : '' }}>Liability</option>
                            <option value="equity" {{ $typeFilter === 'equity' ? 'selected' : '' }}>Equity</option>
                            <option value="income" {{ $typeFilter === 'income' ? 'selected' : '' }}>Income</option>
                            <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                        <select class="coa-in" name="status">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <button type="submit" class="coa-btn coa-btn-cta coa-btn-sm" style="height:42px">{{ __('Filter') }}</button>
                        <div class="coa-toolbar-right">
                            <button type="button" class="coa-btn coa-btn-ghost coa-btn-sm" onclick="document.querySelectorAll('.coa-grp[data-open]').forEach(g=>g.setAttribute('data-open','true'))">{{ __('Expand All') }}</button>
                            <button type="button" class="coa-btn coa-btn-ghost coa-btn-sm" onclick="document.querySelectorAll('.coa-grp[data-open]').forEach(g=>g.setAttribute('data-open','false'))">{{ __('Collapse All') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Accounts tree --}}
        <div class="coa-card">
            <div class="coa-li-wrap">
                <table class="coa-table">
                    <thead>
                        <tr>
                            <th style="width:10%">{{ __('Code') }}</th>
                            <th style="width:34%">{{ __('Account Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Balance') }}</th>
                            <th style="width:8%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($typeOrder as $type)
                            @if($grouped->has($type))
                                @php
                                    $typeAccounts = $grouped[$type]->sortBy('code');
                                    $typeBalance = 0;
                                    foreach ($typeAccounts as $a) {
                                        $typeBalance += $balances[$a->id] ?? 0;
                                    }
                                @endphp
                                {{-- Group header row --}}
                                <tr class="coa-grp" data-open="true" onclick="let open=this.getAttribute('data-open');this.setAttribute('data-open',open==='true'?'false':'true');this.closest('tbody').querySelectorAll('[data-parent={{ $type }}]').forEach(r=>r.style.display=open==='true'?'none':'')">
                                    <td colspan="5">
                                        <span class="coa-grp-chevron">▾</span>
                                        {{ $typeLabels[$type] }}
                                        <span class="coa-grp-count">{{ $typeAccounts->count() }} accounts</span>
                                    </td>
                                    <td></td>
                                </tr>
                                {{-- Account rows --}}
                                @foreach($typeAccounts as $account)
                                    @php $childCount = $accounts->where('parent_id', $account->id)->count(); @endphp
                                    <tr data-parent="{{ $type }}">
                                        <td class="coa-mono">{{ $account->code }}</td>
                                        <td style="font-weight:600;color:var(--ink)">{{ $account->name }}</td>
                                        <td><span class="coa-tchip">{{ ucfirst($type) }}</span></td>
                                        <td>
                                            @if($account->is_active)
                                                <span class="coa-badge coa-b-ok"><span class="bdot"></span>{{ __('Active') }}</span>
                                            @else
                                                <span class="coa-badge coa-b-off"><span class="bdot"></span>{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="num">{{ ($balances[$account->id] ?? 0) != 0 ? format_number($balances[$account->id]) : '—' }}</td>
                                        <td>
                                            <div class="coa-row-act">
                                                <a href="{{ route('accounting.accounts.edit', $account) }}" class="coa-ibtn" title="{{ __('Edit') }}">✎</a>
                                                <form method="POST" action="{{ route('accounting.accounts.toggle', $account) }}" style="display:inline" onsubmit="return fbConfirmSubmit(event, '{{ $account->is_active ? __('Deactivate this account?') : __('Activate this account?') }}', { type: 'danger' })">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="coa-ibtn" title="{{ $account->is_active ? __('Deactivate') : __('Activate') }}">⋯</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Child accounts --}}
                                    @foreach($accounts->where('parent_id', $account->id)->sortBy('code') as $child)
                                        <tr class="coa-child" data-parent="{{ $type }}">
                                            <td class="coa-mono">{{ $child->code }}</td>
                                            <td style="font-weight:600;color:var(--ink)">— {{ $child->name }}</td>
                                            <td><span class="coa-tchip">{{ ucfirst($type) }}</span></td>
                                            <td>
                                                @if($child->is_active)
                                                    <span class="coa-badge coa-b-ok"><span class="bdot"></span>{{ __('Active') }}</span>
                                                @else
                                                    <span class="coa-badge coa-b-off"><span class="bdot"></span>{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td class="num">{{ ($balances[$child->id] ?? 0) != 0 ? format_number($balances[$child->id]) : '—' }}</td>
                                            <td>
                                                <div class="coa-row-act">
                                                    <a href="{{ route('accounting.accounts.edit', $child) }}" class="coa-ibtn" title="{{ __('Edit') }}">✎</a>
                                                    <form method="POST" action="{{ route('accounting.accounts.toggle', $child) }}" style="display:inline" onsubmit="return fbConfirmSubmit(event, '{{ $child->is_active ? __('Deactivate this account?') : __('Activate this account?') }}', { type: 'danger' })">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="coa-ibtn" title="{{ $child->is_active ? __('Deactivate') : __('Activate') }}">⋯</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                {{-- Subtotal row --}}
                                <tr class="coa-sub" data-parent="{{ $type }}">
                                    <td colspan="4" style="text-align:right;font-weight:700">{{ $typeLabels[$type] }} Total</td>
                                    <td class="num">{{ format_number($typeBalance) }}</td>
                                    <td></td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="coa-empty">No accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>

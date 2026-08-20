@php
    $typeClasses = ['asset'=>'mix','liability'=>'cr','equity'=>'cr','income'=>'post','expense'=>'dr'];
    $behaviourClasses = ['both'=>'mix','debit_only'=>'dr','credit_only'=>'cr'];
    $children = $accounts->where('parent_id', $account->id)->sortBy('code')->values();
@endphp
<tr>
    <td class="coa-mono" style="padding-left:{{ 12 + $depth * 20 }}px">{{ $account->display_code }}</td>
    <td style="font-weight:700;color:var(--ink)">{{ $depth > 0 ? '↳ ' : '' }}{{ $account->name }}</td>
    <td><span class="tchip {{ $typeClasses[$account->type] ?? 'mix' }}">{{ ucfirst($account->type) }}</span></td>
    <td><span class="tchip lv">L{{ $account->level }}</span></td>
    <td>
        @if($account->is_system_account)
            <span class="tchip sys">System</span>
        @elseif($account->is_group)
            <span class="tchip grp">Group</span>
        @else
            <span class="tchip post">Posting</span>
        @endif
    </td>
    <td>
        @if($account->is_group)
            <span class="coa-em">—</span>
        @else
            <span class="tchip {{ $behaviourClasses[$account->posting_behaviour] ?? 'mix' }}">{{ $account->posting_behaviour === 'both' ? 'Dr·Cr' : ($account->posting_behaviour === 'debit_only' ? 'Dr only' : 'Cr only') }}</span>
        @endif
    </td>
    <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">{{ number_format($balances[$account->id] ?? 0, 2) }}</td>
    <td><span class="coa-badge {{ $account->is_active ? 'coa-b-ok' : 'coa-b-off' }}"><span class="bdot"></span>{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
    <td class="coa-row-act">
        <a href="{{ route('accounting.coa.edit', $account) }}" class="coa-ibtn" title="Edit">✎</a>
        @if($account->is_system_account)
            <span class="coa-ibtn" style="opacity:.3;cursor:not-allowed" title="System account — cannot delete">🔒</span>
        @endif
    </td>
</tr>
@foreach($children as $child)
    @include('accounting.coa._account-row', ['account' => $child, 'depth' => $depth + 1, 'balances' => $balances, 'accounts' => $accounts])
@endforeach

@php
    $children = $accounts->where('parent_id', $account->id)->sortBy('code')->values();
    $hasChildren = $children->isNotEmpty();
@endphp
<li @if($hasChildren) @click="$el.classList.toggle('closed')" @endif>
    <div class="trow {{ $account->is_group ? 'grp' : '' }}" role="treeitem" @if($hasChildren) aria-expanded="true" @endif>
        <span class="car" style="width:16px;color:var(--faint);transition:transform .15s;flex:none">{{ $hasChildren ? '▾' : '' }}</span>
        <span class="coa-mono" style="font-family:ui-monospace,Menlo,monospace;font-size:11.5px;font-weight:700;color:var(--deep-1);min-width:76px">{{ $account->display_code }}</span>
        <span style="font-size:13px;font-weight:600;color:var(--ink)">{{ $account->name }}</span>
        @if($account->is_system_account)
            <span class="tchip sys">System·locked</span>
        @elseif($account->is_group)
            <span class="tchip grp">Group</span>
        @else
            <span class="tchip post">Posting·{{ $account->isDebitNormal() ? 'Dr' : 'Cr' }}</span>
        @endif
    </div>
    @if($hasChildren)
    <ul>
        @foreach($children as $child)
        @include('accounting.coa._tree-node', ['account' => $child, 'accounts' => $accounts, 'balances' => $balances])
        @endforeach
    </ul>
    @endif
</li>

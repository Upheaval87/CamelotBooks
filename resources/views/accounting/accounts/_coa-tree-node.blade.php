@php
    $cs = $cs ?? 'MWK';
    $nodeId = $node['id'];
    $hasChildren = !empty($node['children']);
    $levelLabel = $node['is_controlled'] ? 'Control' : ($node['is_contra'] ? 'Contra' : ($node['is_group'] ? 'Group' : 'Sub'));
    $levelClass = $node['is_controlled'] ? 'coa2-lv-c' : ($node['is_contra'] ? 'coa2-lv-ct' : ($node['is_group'] ? 'coa2-lv-g' : 'coa2-lv-l'));
    $statusClass = $node['status'] === 'active' ? 'coa2-st-a' : ($node['status'] === 'controlled' ? 'coa2-st-c' : 'coa2-st-i');
    $statusLabel = $node['status'] === 'active' ? __('Active') : ($node['status'] === 'controlled' ? __('Controlled') : __('Inactive'));
@endphp

@if($hasChildren)
    <div class="coa2-node">
        <div class="coa2-row coa2-row-c"
             data-status="{{ $node['status'] === 'active' ? 'Active' : ($node['status'] === 'controlled' ? 'Controlled' : 'Inactive') }}"
             data-open="{{ $node['opening'] }}" data-cur="{{ $node['current'] }}"
             data-id="{{ $nodeId }}" data-code="{{ $node['code'] }}" data-name="{{ $node['name'] }}">
            <button class="coa2-car">&#9660;</button>
            <span class="coa2-code">{{ $node['code'] }}</span>
            <span class="coa2-nm">{{ $node['name'] }}</span>
            <span class="hide-s"><span class="coa2-chip {{ $levelClass }}">{{ $levelLabel }}</span></span>
            <span><span class="coa2-chip {{ $statusClass }}">{{ $node['status'] === 'controlled' ? '&#128274; ' : '' }}{{ $statusLabel }}</span></span>
            <span class="coa2-num">{{ format_number($node['opening']) }}</span>
            <span class="coa2-num {{ $node['current'] < 0 ? 'neg' : '' }}">{{ format_number($node['current']) }}</span>
            <span class="coa2-act">
                <a href="{{ route('accounting.accounts.show', $nodeId) }}" class="coa2-ib" title="{{ __('View') }}">&#128065;</a>
                @if($node['status'] !== 'controlled')
                    <a href="{{ route('accounting.accounts.edit', $nodeId) }}" class="coa2-ib" title="{{ __('Edit') }}">&#9998;</a>
                @endif
            </span>
        </div>
        <div class="coa2-kids">
            @foreach($node['children'] as $child)
                @include('accounting.accounts._coa-tree-node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    </div>
@else
    <div class="coa2-row coa2-leaf"
         data-status="{{ $node['status'] === 'active' ? 'Active' : ($node['status'] === 'controlled' ? 'Controlled' : 'Inactive') }}"
         data-open="{{ $node['opening'] }}" data-cur="{{ $node['current'] }}"
         data-id="{{ $nodeId }}" data-code="{{ $node['code'] }}" data-name="{{ $node['name'] }}">
        <span></span>
        <span class="coa2-code">{{ $node['code'] }}</span>
        <span class="coa2-nm">{{ $node['name'] }}</span>
        <span class="hide-s"><span class="coa2-chip {{ $levelClass }}">{{ $levelLabel }}</span></span>
        <span><span class="coa2-chip {{ $statusClass }}">{{ $node['status'] === 'controlled' ? '&#128274; ' : '' }}{{ $statusLabel }}</span></span>
        <span class="coa2-num">{{ format_number($node['opening']) }}</span>
        <span class="coa2-num {{ $node['current'] < 0 ? 'neg' : '' }}">{{ format_number($node['current']) }}</span>
        <span class="coa2-act">
            <a href="{{ route('accounting.accounts.show', $nodeId) }}" class="coa2-ib" title="{{ __('View') }}">&#128065;</a>
            @if($node['status'] !== 'controlled')
                <a href="{{ route('accounting.accounts.edit', $nodeId) }}" class="coa2-ib" title="{{ __('Edit') }}">&#9998;</a>
            @endif
        </span>
    </div>
@endif

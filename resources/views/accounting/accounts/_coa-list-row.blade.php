@php
    $cs = $cs ?? 'MWK';
    $statusClass = $account['status'] === 'active' ? 'coa2-st-a' : ($account['status'] === 'controlled' ? 'coa2-st-c' : 'coa2-st-i');
    $statusLabel = $account['status'] === 'active' ? __('Active') : ($account['status'] === 'controlled' ? __('Controlled') : __('Inactive'));
    $depth = $account['_depth'] ?? 0;
@endphp

<tr class="coa2-lrow"
    data-status="{{ $account['status'] === 'active' ? 'Active' : ($account['status'] === 'controlled' ? 'Controlled' : 'Inactive') }}"
    data-open="{{ $account['opening'] }}" data-cur="{{ $account['current'] }}"
    data-id="{{ $account['id'] }}" data-code="{{ $account['code'] }}" data-name="{{ $account['name'] }}">
    <td class="coa2-code" style="padding-left:{{ 16 + $depth * 20 }}px">{{ $account['code'] }}</td>
    <td>
        <span class="coa2-nm">{{ $account['name'] }}</span>
        @if($account['description'])
            <span class="coa2-desc">{{ $account['description'] }}</span>
        @endif
    </td>
    <td>{{ \App\Services\Accounting\CoaService::humanizeSubType($account['sub_type']) }}</td>
    <td><span class="coa2-chip {{ $statusClass }}">{{ $account['status'] === 'controlled' ? '&#128274; ' : '' }}{{ $statusLabel }}</span></td>
    <td class="coa2-num">{{ format_number($account['opening']) }}</td>
    <td class="coa2-num {{ $account['current'] < 0 ? 'neg' : '' }}">{{ format_number($account['current']) }}</td>
    <td>
        <a href="{{ route('accounting.accounts.show', $account['id']) }}" class="coa2-ib" title="{{ __('View') }}">&#128065;</a>
        @if($account['status'] !== 'controlled')
            <a href="{{ route('accounting.accounts.edit', $account['id']) }}" class="coa2-ib" title="{{ __('Edit') }}">&#9998;</a>
        @endif
        <button class="coa2-more" title="{{ __('More') }}">&#8943;</button>
    </td>
</tr>

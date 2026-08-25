<div class="tx-chips" style="grid-template-columns:repeat(4,1fr)">
    <div class="tx-chipbox">
        <span class="l">{{ __('Total Types') }}</span>
        <span class="v">{{ $types->count() }}</span>
    </div>
    <div class="tx-chipbox">
        <span class="l">{{ __('Active') }}</span>
        <span class="v" style="color:var(--green,#15803d)">{{ $types->where('active')->count() }}</span>
    </div>
    <div class="tx-chipbox">
        <span class="l">{{ __('Inactive') }}</span>
        <span class="v">{{ $types->where('active', false)->count() }}</span>
    </div>
    <div class="tx-chipbox">
        <span class="l">{{ __('Tax Codes') }}</span>
        <span class="v">{{ $types->sum('tax_codes_count') }}</span>
    </div>
</div>

<div class="tx-card">
    <div class="tx-card-h">
        <span class="ic">&#128203;</span>
        <h2>{{ __('Tax Types') }}</h2>
    </div>
    <div class="tx-li-wrap">
        <table class="tx-table" style="min-width:720px;">
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th class="num">{{ __('Tax Codes') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($types as $type)
                    @php
                        $tchipClass = match ($type->category) {
                            'WHT' => 'tx-t-wht',
                            'PAYE' => 'tx-t-paye',
                            'FBT' => 'tx-t-fbt',
                            default => 'tx-t-vat',
                        };
                    @endphp
                    <tr>
                        <td class="tx-mono tx-name">{{ $type->code }}</td>
                        <td class="tx-name">{{ $type->name }}</td>
                        <td><span class="tx-tchip {{ $tchipClass }}">{{ $type->category }}</span></td>
                        <td class="num">{{ $type->tax_codes_count }}</td>
                        <td>
                            @if ($type->active)
                                <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Active') }}</span>
                            @else
                                <span class="tx-badge tx-b-off"><span class="bdot"></span>{{ __('Inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="tx-em" style="text-align:center;padding:36px;">{{ __('No tax types configured yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

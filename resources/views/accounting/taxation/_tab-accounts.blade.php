<div class="tx-kpis" style="grid-template-columns:1fr 1fr;">
    <div class="tx-kpi {{ $taxPayableAccount ? '' : 'warn' }}">
        <div class="l">{{ __('Output / Payable Account') }}</div>
        <div class="v" style="font-size:1rem;">
            @if ($taxPayableAccount)
                <span class="tx-mono">{{ $taxPayableAccount->code }}</span> &middot; {{ $taxPayableAccount->name }}
            @else
                {{ __('Not configured') }}
            @endif
        </div>
        <div class="n">{{ __('Where sales tax collected is credited.') }}</div>
    </div>
    <div class="tx-kpi {{ $taxReceivableAccount ? '' : 'warn' }}">
        <div class="l">{{ __('Input / Receivable Account') }}</div>
        <div class="v" style="font-size:1rem;">
            @if ($taxReceivableAccount)
                <span class="tx-mono">{{ $taxReceivableAccount->code }}</span> &middot; {{ $taxReceivableAccount->name }}
            @else
                {{ __('Not configured') }}
            @endif
        </div>
        <div class="n">{{ __('Where purchase tax recoverable is debited.') }}</div>
    </div>
</div>

<div class="tx-card">
    <div class="tx-card-h">
        <span class="ic">&#9632;</span>
        <h2>{{ __('Other Mappings') }}</h2>
    </div>
    <div class="tx-li-wrap">
        <table class="tx-table" style="min-width:560px;">
            <thead>
                <tr>
                    <th>{{ __('Mapping') }}</th>
                    <th>{{ __('Account') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($otherMappings as $mapping)
                    <tr>
                        <td class="tx-name">{{ $mapping['label'] }}</td>
                        <td>
                            @if ($mapping['account'])
                                <span class="tx-mono">{{ $mapping['account']->code }}</span> &middot; {{ $mapping['account']->name }}
                            @else
                                <span class="tx-em">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            @if ($mapping['account'])
                                <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Mapped') }}</span>
                            @else
                                <span class="tx-badge tx-b-pend"><span class="bdot"></span>{{ __('Not set') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No mappings defined yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="tx-em" style="margin-top:12px;">{{ __('Mappings are maintained in System Settings &rarr; Default Accounts.') }}{!! '' !!}</p>

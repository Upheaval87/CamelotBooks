<div class="tx-grid2" style="grid-template-columns:1fr 1fr;">
    <div class="tx-card" style="margin-bottom:0">
        <div class="tx-card-h">
            <span class="ic" style="background:rgba(22,163,74,.1);color:var(--green,#15803d)">&#128179;</span>
            <h2>{{ __('Output / Payable Account') }}</h2>
        </div>
        <div class="tx-pad">
            @if ($taxPayableAccount)
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Account') }}</span>
                    <span class="v"><span class="tx-mono">{{ $taxPayableAccount->code }}</span> &middot; {{ $taxPayableAccount->name }}</span>
                </div>
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Description') }}</span>
                    <span class="v tx-em">{{ __('Where sales tax collected is credited.') }}</span>
                </div>
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Status') }}</span>
                    <span class="v"><span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Mapped') }}</span></span>
                </div>
            @else
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Account') }}</span>
                    <span class="v tx-em">{{ __('Not configured') }}</span>
                </div>
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Status') }}</span>
                    <span class="v"><span class="tx-badge tx-b-pend"><span class="bdot"></span>{{ __('Not set') }}</span></span>
                </div>
            @endif
        </div>
    </div>

    <div class="tx-card" style="margin-bottom:0">
        <div class="tx-card-h">
            <span class="ic" style="background:rgba(18,143,142,.1);color:var(--sec,#128F8E)">&#128179;</span>
            <h2>{{ __('Input / Receivable Account') }}</h2>
        </div>
        <div class="tx-pad">
            @if ($taxReceivableAccount)
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Account') }}</span>
                    <span class="v"><span class="tx-mono">{{ $taxReceivableAccount->code }}</span> &middot; {{ $taxReceivableAccount->name }}</span>
                </div>
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Description') }}</span>
                    <span class="v tx-em">{{ __('Where purchase tax recoverable is debited.') }}</span>
                </div>
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Status') }}</span>
                    <span class="v"><span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Mapped') }}</span></span>
                </div>
            @else
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Account') }}</span>
                    <span class="v tx-em">{{ __('Not configured') }}</span>
                </div>
                <div class="tx-dl-simple">
                    <span class="l">{{ __('Status') }}</span>
                    <span class="v"><span class="tx-badge tx-b-pend"><span class="bdot"></span>{{ __('Not set') }}</span></span>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="tx-card" style="margin-top:16px">
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
                    <tr><td colspan="3" class="tx-em" style="text-align:center;padding:36px;">{{ __('No mappings defined yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="tx-em" style="margin-top:12px;">{{ __('Mappings are maintained in System Settings &rarr; Default Accounts.') }}</p>

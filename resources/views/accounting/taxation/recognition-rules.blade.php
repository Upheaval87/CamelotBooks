<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Recognition Rules') }}</h1>
                <p class="sub">{{ __('When each tax type is recognised — invoice basis, cash basis or accrual.') }}</p>
            </div>
        </div>

        @php
            $basisMap = [
                'INVOICE' => ['tx-t-vat', __('Invoice')],
                'CASH' => ['tx-t-cash', __('Cash')],
                'PAYMENT' => ['tx-t-cash', __('Payment')],
                'ACCRUAL' => ['tx-t-fbt', __('Accrual')],
            ];
            $basisOptions = [
                'INVOICE' => __('Invoice'),
                'CASH' => __('Cash'),
                'PAYMENT' => __('Payment'),
                'ACCRUAL' => __('Accrual'),
            ];
        @endphp

        <div class="tx-card">
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:760px;">
                    <thead>
                        <tr>
                            <th>{{ __('Tax Type') }}</th>
                            <th>{{ __('Recognition Basis') }}</th>
                            <th>{{ __('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            @php [$chipClass, $basisLabel] = $basisMap[$rule->basis] ?? ['tx-t-vat', ucfirst(strtolower((string) $rule->basis))];
                            @endphp
                            <tr>
                                <td><span class="tx-tchip tx-t-vat">{{ $rule->taxType?->name ?? '&mdash;' }}</span></td>
                                <td><span class="tx-tchip {{ $chipClass }}">{{ $basisLabel }}</span></td>
                                <td class="tx-em">{{ $rule->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No recognition rules configured yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($typesWithoutRule->isNotEmpty())
            <div class="tx-note">
                {{ __('No rule is set for:') }}
                @foreach ($typesWithoutRule as $missingType)
                    <strong>{{ $missingType->name }}</strong>@if(! $loop->last), @endif
                @endforeach
                &mdash; {{ __('these types follow the default invoice basis.') }}
            </div>
        @endif

        <div class="tx-card">
            <div class="tx-pad" style="display:flex;gap:24px;flex-wrap:wrap;">
                @foreach ($basisOptions as $key => $label)
                    <div style="min-width:180px;">
                        <span class="tx-badge tx-b-post"><span class="bdot"></span>{{ $label }}</span>
                        <p class="tx-em" style="margin-top:6px;font-size:11.5px;line-height:1.5;">
                            @switch($key)
                                @case('INVOICE')
                                    {{ __('Tax is recognised when the document is raised.') }}
                                    @break
                                @case('CASH')
                                    {{ __('Tax is recognised when money changes hands.') }}
                                    @break
                                @case('PAYMENT')
                                    {{ __('Tax is recognised when payment settles.') }}
                                    @break
                                @default
                                    {{ __('Tax follows the accounting accrual date.') }}
                            @endswitch
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

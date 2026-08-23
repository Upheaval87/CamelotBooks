<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Types') }}</h1>
                <p class="sub">{{ __('The families of tax your company administers — VAT, withholding, payroll and more.') }}</p>
            </div>
        </div>

        @include('accounting.taxation._tabs', ['active' => 'types'])

        <div class="tx-card">
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
                                <td>{{ $type->name }}</td>
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
                            <tr><td colspan="5" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No tax types configured yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

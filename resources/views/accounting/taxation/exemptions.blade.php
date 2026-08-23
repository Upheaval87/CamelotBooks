<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Exemptions') }}</h1>
                <p class="sub">{{ __('Items and transactions exempt from tax, with the reason for each.') }}</p>
            </div>
        </div>

        @include('accounting.taxation._tabs', ['active' => 'exemptions'])

        <div class="tx-card">
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:860px;">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Scope') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exemptions as $exemption)
                            <tr>
                                <td class="tx-mono tx-name">{{ $exemption->code }}</td>
                                <td>{{ $exemption->name }}</td>
                                <td><span class="tx-tchip tx-t-vat">{{ $exemption->taxType?->name ?? '&mdash;' }}</span></td>
                                <td class="tx-em">{{ Str::limit($exemption->scope ?? '', 60) }}</td>
                                <td class="tx-em">{{ Str::limit($exemption->reason ?? '', 80) }}</td>
                                <td>
                                    @if ($exemption->active)
                                        <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Active') }}</span>
                                    @else
                                        <span class="tx-badge tx-b-off"><span class="bdot"></span>{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No exemptions configured yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

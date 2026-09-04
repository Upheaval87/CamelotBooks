@include('accounting.taxation._create-exemption')

<div class="tx-card">
    <div class="tx-card-h">
        <span class="ic">&#128683;</span>
        <h2>{{ __('Tax Exemptions') }}</h2>
    </div>
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
                        <td class="tx-name">{{ $exemption->name }}</td>
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
                    <tr><td colspan="6" class="tx-em" style="text-align:center;padding:36px;">{{ __('No exemptions configured yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

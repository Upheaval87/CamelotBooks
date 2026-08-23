<div class="tx-card">
    <div class="tx-li-wrap">
        <table class="tx-table" style="min-width:760px;">
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Country') }}</th>
                    <th>{{ __('Authority') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jurisdictions as $jurisdiction)
                    <tr>
                        <td class="tx-mono tx-name">{{ $jurisdiction->code }}</td>
                        <td>{{ $jurisdiction->name }}</td>
                        <td>{{ $jurisdiction->country }}</td>
                        <td>{{ $jurisdiction->authority }}</td>
                        <td>
                            @if ($jurisdiction->active)
                                <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Active') }}</span>
                            @else
                                <span class="tx-badge tx-b-off"><span class="bdot"></span>{{ __('Inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No jurisdictions configured yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

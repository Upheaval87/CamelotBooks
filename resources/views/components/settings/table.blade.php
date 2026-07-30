@props(['headers' => [], 'rows' => [], 'emptyMessage' => 'No data available.'])

<div class="settings-table-wrapper">
    <table class="settings-table">
        @if(count($headers) > 0)
        <thead>
            <tr>
                @foreach($headers as $header)
                <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        @endif
        <tbody>
            @if(count($rows) > 0)
                {{ $slot }}
            @else
                <tr>
                    <td colspan="{{ count($headers) }}" class="settings-table-empty">{{ $emptyMessage }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

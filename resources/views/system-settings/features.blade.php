<div class="card">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="form-section-label">1 · Feature Management</div>
        <p class="mt-1 text-sm text-ink-soft">Module activation is controlled by your system administrator.</p>
    </div>

    <div class="px-6 py-4">
        <div class="rounded-md border border-gold-line bg-gold-soft/40 px-4 py-3 text-sm text-ink-soft">
            Feature activation is managed centrally from the Super Admin panel. Disabled features are hidden from
            navigation and inaccessible to users. To request a change, contact your system administrator.
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="datasheet">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($features as $key => $label)
                    @php $isOn = array_key_exists($key, $enabled); @endphp
                    <tr>
                        <td>
                            <div>{{ $label }}</div>
                            <div class="text-ink-soft">{{ $key }}</div>
                        </td>
                        <td>
                            @if($isOn)
                                <span class="status-pill positive">Enabled</span>
                            @else
                                <span class="status-pill negative">Disabled</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

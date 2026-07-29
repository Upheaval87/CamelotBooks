<div class="card">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="form-section-label">1 · Feature Management</div>
        <p class="mt-1 text-sm text-ink-soft">Toggle modules on or off for this company. Disabled features are hidden from navigation and inaccessible to users.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="datasheet">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
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
                        <td class="text-right">
                            <form method="POST" action="{{ route('system-settings.features.toggle', $key) }}" class="inline">
                                @csrf
                                @if($isOn)
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-900 font-medium">Disable</button>
                                @else
                                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Enable</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Feature Management</h3>
        <p class="mt-1 text-sm text-gray-600">Toggle modules on or off for this company. Disabled features are hidden from navigation and inaccessible to users.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($features as $key => $label)
                    @php $isOn = array_key_exists($key, $enabled); @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $label }}</div>
                            <div class="text-xs text-gray-500">{{ $key }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($isOn)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Enabled</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">Disabled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
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

<form method="POST" action="{{ route('system-settings.update-numbering') }}">
    @csrf
    @method('PUT')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Document Numbering Overrides</h3>
            <p class="mt-1 text-sm text-gray-600">Customize the prefix, padding, and reset policy for each document type. Changes here update the numbering sequences used when generating document numbers.</p>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">Active</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Prefix</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Padding</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-36">Reset Policy</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preview</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($documentTypeLabels as $type => $label)
                            @php
                                $seq = $sequences->firstWhere('document_type', $type);
                                $preview = $nextNumbers[$type] ?? '-';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input type="hidden" name="active.{{ $type }}" value="0">
                                    <input type="checkbox" name="active.{{ $type }}" value="1"
                                        {{ $seq && $seq->is_active ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $label }}</td>
                                <td class="px-4 py-3">
                                    <input type="text" name="prefixes[{{ $type }}]" value="{{ $seq->prefix ?? '' }}"
                                        maxlength="20"
                                        class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="padding_widths[{{ $type }}]" value="{{ $seq->padding_width ?? 4 }}"
                                        min="1" max="10"
                                        class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                                </td>
                                <td class="px-4 py-3">
                                    <select name="reset_policies[{{ $type }}]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="never" {{ ($seq->reset_policy ?? 'never') === 'never' ? 'selected' : '' }}>Never</option>
                                        <option value="annually" {{ ($seq->reset_policy ?? 'never') === 'annually' ? 'selected' : '' }}>Annually</option>
                                        <option value="monthly" {{ ($seq->reset_policy ?? 'never') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    @if($seq && $seq->is_active)
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $preview }}</code>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Save Numbering Settings
            </button>
        </div>
    </div>
</form>

<x-app-layout>
    <x-slot name="header">{{ __('Account Classification') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Classify accounts for Cash Flow Statement reporting</h3>
                    <p class="text-sm text-gray-500 mt-1">Assign cash flow section and non-cash flags to accounts used in the indirect method cash flow statement.</p>
                </div>
                <div class="overflow-x-auto">
                    <form method="POST" action="{{ route('accounting.account-classification.update') }}">
                        @csrf
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Account Name</th>
                                    <th>Type</th>
                                    <th>Cash Flow Section</th>
                                    <th class="text-center">Non-Cash</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounts as $account)
                                    <tr class="hover:bg-gray-50">
                                        <td>{{ $account->code }}</td>
                                        <td>{{ $account->name }}</td>
                                        <td class="text-ink-soft">{{ ucfirst($account->type) }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <select name="cash_flow_sections[{{ $account->id }}]" class="block w-48 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                                <option value="">None</option>
                                                <option value="operating" {{ ($account->cash_flow_section ?? '') === 'operating' ? 'selected' : '' }}>Operating</option>
                                                <option value="investing" {{ ($account->cash_flow_section ?? '') === 'investing' ? 'selected' : '' }}>Investing</option>
                                                <option value="financing" {{ ($account->cash_flow_section ?? '') === 'financing' ? 'selected' : '' }}>Financing</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="is_non_cash[{{ $account->id }}]" value="1" {{ ($account->is_non_cash ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-ink-soft">No accounts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <x-primary-button type="submit">{{ __('Save Classifications') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
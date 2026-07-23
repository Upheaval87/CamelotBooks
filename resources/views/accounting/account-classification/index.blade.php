<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Account Classification') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Classify accounts for Cash Flow Statement reporting</h3>
                    <p class="text-sm text-gray-500 mt-1">Assign cash flow section and non-cash flags to accounts used in the indirect method cash flow statement.</p>
                </div>
                <div class="overflow-x-auto">
                    <form method="POST" action="{{ route('accounting.account-classification.update') }}">
                        @csrf
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cash Flow Section</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Non-Cash</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($accounts as $account)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $account->code }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $account->name }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($account->type) }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <select name="cash_flow_sections[{{ $account->id }}]" class="block w-48 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                                <option value="">None</option>
                                                <option value="operating" {{ ($account->cash_flow_section ?? '') === 'operating' ? 'selected' : '' }}>Operating</option>
                                                <option value="investing" {{ ($account->cash_flow_section ?? '') === 'investing' ? 'selected' : '' }}>Investing</option>
                                                <option value="financing" {{ ($account->cash_flow_section ?? '') === 'financing' ? 'selected' : '' }}>Financing</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-center">
                                            <input type="checkbox" name="is_non_cash[{{ $account->id }}]" value="1" {{ ($account->is_non_cash ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No accounts found.</td>
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
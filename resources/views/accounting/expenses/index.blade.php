<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Record Expense') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.expenses.create') }}">
                    {{ __('Record Expense') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.expenses.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                        <x-scoped-search-field
                            name="vendor_id"
                            entity="vendor"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                            :value="request('vendor_id')"
                            :label="request('vendor_id') ? ($vendors->firstWhere('id', (int) request('vendor_id'))?->name ?? '') : ''"
                            placeholder="{{ __('Search vendors...') }}"
                        />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="from_date" value="{{ __('From') }}" />
                        <x-text-input id="from_date" name="from_date" type="date" class="mt-1 block w-full" :value="request('from_date')" />
                    </div>
                    <div>
                        <x-input-label for="to_date" value="{{ __('To') }}" />
                        <x-text-input id="to_date" name="to_date" type="date" class="mt-1 block w-full" :value="request('to_date')" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('vendor_id') || request('status') || request('from_date') || request('to_date'))
                            <a href="{{ route('accounting.expenses.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Expense #</th>
                                <th>Vendor</th>
                                <th>Date</th>
                                <th class="text-right">Amount ({{ $cs }})</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.expenses.show', $expense) }}" class="text-ink hover:text-gold">
                                            {{ $expense->expense_number }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $expense->vendor->name ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $expense->expense_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($expense->amount) }}
                                    </td>
                                    <td class="text-center">
                                        @switch($expense->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('posted')
                                                <span class="status-pill positive">Posted</span>
                                                @break
                                            @case('void')
                                                <span class="status-pill neutral">Void</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.expenses.show', $expense) }}" class="text-ink hover:text-gold">View</a>
                                        @if($expense->status === 'draft')
                                            <a href="{{ route('accounting.expenses.edit', $expense) }}" class="text-ink hover:text-gold">Edit</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No expenses found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($expenses->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $expenses->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

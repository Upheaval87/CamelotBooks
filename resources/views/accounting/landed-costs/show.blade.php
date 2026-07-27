<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Landed Cost:') }} {{ $voucher->voucher_number }}</h2>
            <div class="flex items-center gap-3">
                @if($voucher->status === 'draft')
                    <form method="POST" action="{{ route('accounting.landed-costs.post', $voucher) }}" onsubmit="return confirm('Post this landed cost voucher?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">Post Voucher</button>
                    </form>
                @endif
                <a href="{{ route('accounting.landed-costs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-3 gap-6 text-sm">
                    <div><span class="text-gray-500">Voucher Number</span><p class="font-medium text-gray-900">{{ $voucher->voucher_number }}</p></div>
                    <div><span class="text-gray-500">Date</span><p class="font-medium text-gray-900">{{ $voucher->date->format('M d, Y') }}</p></div>
                    <div><span class="text-gray-500">Status</span><p>
                        @if($voucher->status === 'posted')<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Posted</span>
                        @elseif($voucher->status === 'draft')<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                        @else<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ ucfirst($voucher->status) }}</span>@endif
                    </p></div>
                    <div><span class="text-gray-500">Vendor</span><p class="font-medium text-gray-900">{{ $voucher->vendor->name ?? 'N/A' }}</p></div>
                    <div><span class="text-gray-500">Allocation Method</span><p class="font-medium text-gray-900">{{ str_replace('_', ' ', ucfirst($voucher->allocation_method)) }}</p></div>
                    <div><span class="text-gray-500">Total Amount</span><p class="font-medium text-gray-900">{{ format_money($voucher->total_amount) }}</p></div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Cost Components</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Payee Account</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($voucher->components as $component)
                            <tr>
                                <td class="px-4 py-2">{{ ucfirst($component->component_type) }}</td>
                                <td class="px-4 py-2">{{ $component->description }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ format_money($component->amount) }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $component->payeeAccount->code ?? '' }} - {{ $component->payeeAccount->name ?? '' }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="2" class="px-4 py-2 text-right">Total:</td>
                            <td class="px-4 py-2 text-right">{{ format_money($voucher->total_amount) }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Linked GRNs</h3>
                <div class="space-y-2">
                    @foreach($voucher->grns as $grn)
                        <div class="flex items-center justify-between p-3 border rounded bg-gray-50 text-sm">
                            <div><span class="font-medium">{{ $grn->grn_number }}</span> &mdash; {{ $grn->date->format('M d, Y') }}</div>
                            <div class="text-right">{{ $grn->lines->count() }} line(s) &mdash; {{ format_money($grn->lines->sum('total_cost')) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($voucher->journalEntry)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Journal Entry: {{ $voucher->journalEntry->journal_number }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit ({{ $cs }})</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Credit ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($voucher->journalEntry->lines as $line)
                                <tr>
                                    <td class="px-4 py-2">{{ $line->account->code ?? '' }} - {{ $line->account->name ?? '' }}</td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900">
                                        {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900">
                                        {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

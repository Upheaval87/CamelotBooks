<x-app-layout>
    <x-slot name="header">{{ __('Vendor Credit') }} #{{ $vendorCredit->vendor_credit_number }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        @if($vendorCredit->status === 'draft')
            <form method="POST" action="{{ route('accounting.vendor-credits.issue', $vendorCredit) }}" class="inline">
                @csrf
                <x-button variant="primary" type="submit">{{ __('Issue') }}</x-button>
            </form>
        @endif
        @if($vendorCredit->status !== 'void')
            <form method="POST" action="{{ route('accounting.vendor-credits.void', $vendorCredit) }}" class="inline">
                @csrf
                @method('PATCH')
                <x-button variant="ghost" type="submit" onclick="return confirm('Are you sure you want to void this vendor credit?')">{{ __('Void') }}</x-button>
            </form>
        @endif
        <x-button variant="ghost" href="{{ route('accounting.vendor-credits.index') }}">{{ __('Back to Vendor Credits') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Credit Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendorCredit->vendor_credit_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @switch($vendorCredit->status)
                                @case('draft')
                                    <span class="status-pill neutral">Draft</span>
                                    @break
                                @case('issued')
                                    <span class="status-pill neutral">Issued</span>
                                    @break
                                @case('applied')
                                    <span class="status-pill positive">Applied</span>
                                    @break
                                @case('void')
                                    <span class="status-pill neutral">Void</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Vendor') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendorCredit->vendor->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendorCredit->credit_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    @if($vendorCredit->bill)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Reference Bill') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('accounting.bills.show', $vendorCredit->bill) }}" class="text-ink hover:text-gold">
                                    {{ $vendorCredit->bill->bill_number }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($vendorCredit->reason)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Reason') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $vendorCredit->reason }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Line Items') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Expense Account</th>
                                <th>Description</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Tax</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendorCredit->lines as $line)
                                <tr>
                                    <td>{{ $line->expenseAccount->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $line->description }}</td>
                                    <td class="numeric">{{ $line->quantity }}</td>
                                    <td class="numeric">{{ format_money($line->unit_price) }}</td>
                                    <td class="numeric">{{ $line->tax_rate }}%</td>
                                    <td class="numeric">{{ format_money($line->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal:</span>
                            <span class="text-gray-900">{{ format_money($vendorCredit->subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tax:</span>
                            <span class="text-gray-900">{{ format_money($vendorCredit->tax_total) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold border-t pt-2">
                            <span class="text-gray-800">Total:</span>
                            <span class="text-gray-900">{{ format_money($vendorCredit->total) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Applied:</span>
                            <span class="text-gray-900">{{ format_money($vendorCredit->amount_applied) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold border-t pt-2">
                            <span class="text-gray-800">Available:</span>
                            <span class="text-gray-900">{{ format_money($vendorCredit->available) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

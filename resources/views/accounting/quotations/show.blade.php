<x-app-layout>
    <x-slot name="header">Quotation {{ $quotation->quotation_number }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.quotations.print', $quotation) }}" target="_blank">Print</x-button>
        @if($quotation->customer && $quotation->customer->email)
            <form method="POST" action="{{ route('accounting.quotations.email', $quotation) }}" class="inline">
                @csrf
                <x-button variant="ghost" type="submit">Email</x-button>
            </form>
        @endif
        <x-button variant="ghost" href="{{ route('accounting.quotations.index') }}">Back to Quotations</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>@endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-6">
                    <div><span class="text-sm text-gray-500">Status:</span>
                        @switch($quotation->status)
                            @case('draft') <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>@break
                            @case('sent') <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Sent</span>@break
                            @case('accepted') <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Accepted</span>@break
                            @case('declined') <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Declined</span>@break
                            @case('converted') <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Converted</span>@break
                            @case('void') <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">Void</span>@break
                        @endswitch
                    </div>
                    <div><span class="text-sm text-gray-500">Customer:</span> <span class="ml-2 text-gray-900">{{ $quotation->customer->name ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Date:</span> <span class="ml-2 text-gray-900">{{ $quotation->quotation_date?->format('M d, Y') ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Valid Until:</span> <span class="ml-2 text-gray-900">{{ $quotation->valid_until?->format('M d, Y') ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Reference:</span> <span class="ml-2 text-gray-900">{{ $quotation->reference ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Memo:</span> <span class="ml-2 text-gray-900">{{ $quotation->memo ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Created By:</span> <span class="ml-2 text-gray-900">{{ $quotation->createdByUser->name ?? '—' }}</span></div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Line Items</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead><tr>
                            <th>Product</th>
                            <th>Description</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Tax</th>
                            <th class="text-right">Total</th>
                        </tr></thead>
                        <tbody>
                            @foreach($quotation->lines as $line)
                                <tr>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $line->description }}</td>
                                    <td class="numeric">{{ number_format($line->quantity, 2) }}</td>
                                    <td class="numeric">{{ format_money($line->unit_price) }}</td>
                                    <td class="numeric">{{ format_money($line->tax_amount) }}</td>
                                    <td class="numeric">{{ format_money($line->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4"><div class="w-64 space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal:</span><span class="text-gray-900">{{ format_money($quotation->amount) }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Tax:</span><span class="text-gray-900">{{ format_money($quotation->tax_total) }}</span></div>
                    <div class="flex justify-between text-sm font-semibold border-t pt-2"><span class="text-gray-800">Total:</span><span class="text-gray-900">{{ format_money($quotation->total) }}</span></div>
                </div></div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
                <div class="flex flex-wrap gap-3">
                    @if($quotation->status === 'draft')
                        <a href="{{ route('accounting.quotations.edit', $quotation) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 shadow-sm hover:bg-gray-50">Edit</a>
                        @if($quotation->customer && $quotation->customer->email)
                            <form method="POST" action="{{ route('accounting.quotations.email', $quotation) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 shadow-sm hover:bg-gray-50">Email to Customer</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('accounting.quotations.send', $quotation) }}" class="inline">@csrf<button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-blue-500">Mark as Sent</button></form>
                    @endif
                    @if($quotation->status === 'sent')
                        <form method="POST" action="{{ route('accounting.quotations.accept', $quotation) }}" class="inline">@csrf<button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-green-500">Accept</button></form>
                        <form method="POST" action="{{ route('accounting.quotations.decline', $quotation) }}" class="inline">@csrf<button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-red-500">Decline</button></form>
                    @endif
                    @if(in_array($quotation->status, ['sent', 'accepted']))
                        <form method="POST" action="{{ route('accounting.quotations.convert-to-invoice', $quotation) }}" class="inline">@csrf<button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-indigo-500">Convert to Invoice</button></form>
                    @endif
                    @if(in_array($quotation->status, ['draft', 'sent', 'accepted']))
                        <form method="POST" action="{{ route('accounting.quotations.void', $quotation) }}" class="inline" onsubmit="var r=prompt('Enter void reason:');if(!r)return false;this.void_reason.value=r;">
                            @csrf<input type="hidden" name="void_reason" value="" />
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 rounded-md font-semibold text-xs text-red-700 shadow-sm hover:bg-red-50">Void</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

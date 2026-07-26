<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Stock Transfer') }} {{ $transfer->transfer_number }}</h2>
            <a href="{{ route('accounting.stock-transfers.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $transfer->transfer_number }}</h3>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        {{ $transfer->date->format('M d, Y') }}
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Product</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $transfer->product->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">SKU</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $transfer->product->sku ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">From Branch</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $transfer->fromBranch->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">To Branch</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $transfer->toBranch->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Quantity Transferred</dt>
                        <dd class="text-sm text-gray-900 font-bold mt-1">{{ number_format($transfer->quantity, 4) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Created By</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $transfer->creator->name ?? '—' }}</dd>
                    </div>
                    @if($transfer->memo)
                        <div class="col-span-2">
                            <dt class="text-sm text-gray-500">Memo</dt>
                            <dd class="text-sm text-gray-900 font-medium mt-1">{{ $transfer->memo }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>

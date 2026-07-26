<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Cheque') }} #{{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}
            </h2>
            <a href="{{ route('accounting.cheques.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Cheque Number</p>
                        <p class="mt-1 text-sm text-gray-900 font-semibold">{{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Date</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $cheque->date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Payee</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $cheque->payee }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Amount</p>
                        <p class="mt-1 text-sm text-gray-900 font-semibold text-lg">{{ format_money($cheque->amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Bank Account</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $cheque->bankAccount->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Status</p>
                        <p class="mt-1">
                            @if($cheque->status === 'outstanding')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Outstanding</span>
                            @elseif($cheque->status === 'cleared')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Cleared</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Void</span>
                            @endif
                        </p>
                    </div>
                    @if($cheque->memo)
                        <div class="col-span-2">
                            <p class="text-xs font-medium text-gray-500 uppercase">Memo</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $cheque->memo }}</p>
                        </div>
                    @endif
                    @if($cheque->journal_entry_id)
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Journal Entry</p>
                            <p class="mt-1 text-sm">
                                <a href="{{ route('accounting.journal-entries.show', $cheque->journal_entry_id) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $cheque->journalEntry->journal_number ?? $cheque->journal_entry_id }}
                                </a>
                            </p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Created By</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $cheque->createdBy->name ?? '—' }}</p>
                    </div>
                </div>

                @if($cheque->voided_at)
                    <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-800">This cheque was voided on {{ $cheque->voided_at->format('M d, Y') }} by {{ $cheque->voidedBy->name ?? 'Unknown' }}.</p>
                    </div>
                @endif

                @if($cheque->status === 'outstanding')
                    <div class="mt-6 flex justify-end">
                        <form method="POST" action="{{ route('accounting.cheques.void', $cheque->id) }}" onsubmit="return confirm('Are you sure you want to void this cheque?')">
                            @csrf
                            <x-danger-button type="submit">{{ __('Void Cheque') }}</x-danger-button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

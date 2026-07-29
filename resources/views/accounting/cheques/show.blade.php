<x-app-layout>
    <x-slot name="header">{{ __('Cheque') }} #{{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>
                @if($cheque->status === 'outstanding')
                    <form method="POST" action="{{ route('accounting.cheques.void', $cheque->id) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to void this cheque?') }}')">
                        @csrf
                        <button type="submit" class="tr-archive">{{ __('Void Cheque') }}</button>
                    </form>
                @endif
                <a href="{{ route('accounting.cheques.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('Cheque Number')" :value="str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT)" />
                    <x-detail-field :label="__('Date')" :value="$cheque->date->format('M d, Y')" />
                    <x-detail-field :label="__('Payee')" :value="$cheque->payee" />
                    <x-detail-field :label="__('Amount')" value-class="text-lg font-semibold text-ink">
                        {{ format_money($cheque->amount) }}
                    </x-detail-field>
                    <x-detail-field :label="__('Bank Account')" :value="$cheque->bankAccount->name ?? '—'" />
                    <x-detail-field :label="__('Status')">
                        @if($cheque->status === 'outstanding')
                            <span class="status-pill neutral">{{ __('Outstanding') }}</span>
                        @elseif($cheque->status === 'cleared')
                            <span class="status-pill positive">{{ __('Cleared') }}</span>
                        @else
                            <span class="status-pill negative">{{ __('Void') }}</span>
                        @endif
                    </x-detail-field>
                    @if($cheque->memo)
                        <x-detail-field :label="__('Memo')" :value="$cheque->memo" class="col-span-3" />
                    @endif
                    @if($cheque->journal_entry_id)
                        <x-detail-field :label="__('Journal Entry')">
                            <a href="{{ route('accounting.journal-entries.show', $cheque->journal_entry_id) }}" class="text-ink hover:text-gold">
                                {{ $cheque->journalEntry->journal_number ?? $cheque->journal_entry_id }}
                            </a>
                        </x-detail-field>
                    @endif
                    <x-detail-field :label="__('Created By')" :value="$cheque->createdBy->name ?? '—'" />
                </div>

                @if($cheque->voided_at)
                    <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-800">{{ __('This cheque was voided on') }} {{ $cheque->voided_at->format('M d, Y') }} {{ __('by') }} {{ $cheque->voidedBy->name ?? 'Unknown' }}.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

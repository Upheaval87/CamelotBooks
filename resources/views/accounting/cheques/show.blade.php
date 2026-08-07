<x-app-layout>
    <x-list-header title="{{ __('Cheque') }} #{{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>
                @if($cheque->status === 'outstanding')
                    @can('cheques.void')
                        <form method="POST" action="{{ route('accounting.cheques.void', $cheque->id) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to void this cheque?') }}')">
                            @csrf
                            <button type="submit" class="tr-archive">{{ __('Void Cheque') }}</button>
                        </form>
                    @endcan
                @endif
                <a href="{{ route('accounting.cheques.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field :label="__('Cheque Number')" :value="str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT)" />
                            <x-detail-field :label="__('Date')" :value="$cheque->date->format('M d, Y')" />
                            <x-detail-field :label="__('Payee')" :value="$cheque->payee" />
                            <x-detail-field :label="__('Amount')" value-class="text-lg font-semibold text-ink">
                                {{ format_money($cheque->amount) }}
                            </x-detail-field>
                            <x-detail-field :label="__('Bank Account')" :value="$cheque->bankAccount->name ?? '—'" />
                            <x-detail-field :label="__('Status')" noBorder>
                                @if($cheque->status === 'outstanding')
                                    <span class="status-pill neutral">{{ __('Outstanding') }}</span>
                                @elseif($cheque->status === 'cleared')
                                    <span class="status-pill positive">{{ __('Cleared') }}</span>
                                @else
                                    <span class="status-pill negative">{{ __('Void') }}</span>
                                @endif
                            </x-detail-field>
                            @if($cheque->journal_entry_id)
                                <x-detail-field :label="__('Journal Entry')">
                                    <a href="{{ route('accounting.journal-entries.show', $cheque->journal_entry_id) }}" class="text-ink hover:text-gold">
                                        {{ $cheque->journalEntry->journal_number ?? $cheque->journal_entry_id }}
                                    </a>
                                </x-detail-field>
                            @endif
                            <x-detail-field :label="__('Created By')" :value="$cheque->createdBy->name ?? '—'" />
                            @if($cheque->memo)
                                <x-detail-field :label="__('Description')" :value="$cheque->memo" class="col-span-3" />
                            @endif
                        </div>

                        @if($cheque->voided_at)
                            <x-feedback.alert variant="error" class="mt-6">{{ __('This cheque was voided on') }} {{ $cheque->voided_at->format('M d, Y') }} {{ __('by') }} {{ $cheque->voidedBy->name ?? 'Unknown' }}.</x-feedback.alert>
                        @endif
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.cheques.print', $cheque), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.cheques.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>

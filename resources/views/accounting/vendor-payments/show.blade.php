<x-app-layout>
    <x-slot name="header">{{ __('Vendor Payment') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>

                @if($payment->status !== 'void')
                    <form method="POST" action="{{ route('accounting.vendor-payments.void', $payment) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tr-archive" onclick="return confirm('{{ __('Are you sure you want to void this payment?') }}')">{{ __('Void') }}</button>
                    </form>
                @endif

                <a href="{{ route('accounting.bills.index') }}" class="tr-item">{{ __('Back to Bills') }}</a>
            </x-record-toolbar>

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

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('Vendor')" :value="$payment->vendor->name ?? '—'" />
                    <x-detail-field :label="__('Status')">
                        @if($payment->status === 'void')
                            <span class="status-pill neutral">{{ __('Void') }}</span>
                        @else
                            <span class="status-pill positive">{{ __('Completed') }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field :label="__('Payment Date')" :value="$payment->payment_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Amount')" value-class="text-2xl font-bold text-ink">
                        {{ format_money($payment->amount) }}
                    </x-detail-field>
                    <x-detail-field :label="__('Payment Method')" :value="ucfirst(str_replace('_', ' ', $payment->payment_method))" />
                    <x-detail-field :label="__('Bank Account')" :value="$payment->bankAccount->name ?? '—'" />
                    @if($payment->reference)
                        <x-detail-field :label="__('Reference')" :value="$payment->reference" />
                    @endif
                    @if($payment->memo)
                        <x-detail-field :label="__('Memo')" :value="$payment->memo" />
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Bill Allocations') }}</p>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>{{ __('Bill #') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-right">{{ __('Bill Total') }}</th>
                                <th class="text-right">{{ __('Amount Allocated') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payment->bills as $bill)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.bills.show', $bill) }}" class="text-ink hover:text-gold">
                                            {{ $bill->bill_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $bill->bill_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($bill->total) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($bill->pivot->amount) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">
                                        {{ __('No allocations found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

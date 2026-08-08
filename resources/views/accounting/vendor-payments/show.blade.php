<x-app-layout>
    <x-list-header title="{{ __('Vendor Payment') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>

                @if($payment->status !== 'void')
                    <form method="POST" action="{{ route('accounting.vendor-payments.void', $payment) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tr-archive" onclick="return fbConfirmButton(event, '{{ __('Are you sure you want to void this payment?') }}', { type: 'danger' })">{{ __('Void') }}</button>
                    </form>
                @endif

                <a href="{{ route('accounting.bills.index') }}" class="tr-item">{{ __('Back to Bills') }}</a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

            

            

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('Vendor')" :value="$payment->vendor->name ?? '—'" />
                    <x-detail-field :label="__('Status')" noBorder>
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
                        <x-detail-field :label="__('Description')" :value="$payment->memo" />
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Bill Allocations') }}</p>
                <div class="overflow-x-auto">
                    <table class="record-datasheet">
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
                                    <td>
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
                                    <td colspan="4" class="text-center">
                                        {{ __('No allocations found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.bills.index'), 'icon' => 'back', 'title' => __('Back to Bills')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>

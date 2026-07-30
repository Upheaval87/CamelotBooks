<x-app-layout>
    <x-slot name="header">{{ __('Vendor Credit') }} #{{ $vendorCredit->vendor_credit_number }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    @if($vendorCredit->status === 'draft')
                        <form method="POST" action="{{ route('accounting.vendor-credits.issue', $vendorCredit) }}" class="inline">
                            @csrf
                            <button type="submit" class="tr-save">{{ __('Issue') }}</button>
                        </form>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                @if($vendorCredit->status !== 'void')
                    @can('vendor-credits.void')
                        <form method="POST" action="{{ route('accounting.vendor-credits.void', $vendorCredit) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tr-archive" onclick="return confirm('{{ __('Are you sure you want to void this vendor credit?') }}')">{{ __('Void') }}</button>
                        </form>
                    @endcan
                @endif

                <a href="{{ route('accounting.vendor-credits.index') }}" class="tr-item">{{ __('Back to Vendor Credits') }}</a>
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

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field :label="__('Credit Number')" :value="$vendorCredit->vendor_credit_number" />
                            <x-detail-field :label="__('Status')" noBorder>
                                @switch($vendorCredit->status)
                                    @case('draft') <span class="status-pill neutral">{{ __('Draft') }}</span> @break
                                    @case('issued') <span class="status-pill neutral">{{ __('Issued') }}</span> @break
                                    @case('applied') <span class="status-pill positive">{{ __('Applied') }}</span> @break
                                    @case('void') <span class="status-pill neutral">{{ __('Void') }}</span> @break
                                @endswitch
                            </x-detail-field>
                            <x-detail-field :label="__('Vendor')" :value="$vendorCredit->vendor->name ?? '—'" />
                            <x-detail-field :label="__('Date')" :value="$vendorCredit->credit_date?->format('M d, Y') ?? '—'" />
                            @if($vendorCredit->bill)
                                <x-detail-field :label="__('Reference Bill')">
                                    <a href="{{ route('accounting.bills.show', $vendorCredit->bill) }}" class="text-ink hover:text-gold">
                                        {{ $vendorCredit->bill->bill_number }}
                                    </a>
                                </x-detail-field>
                            @endif
                            @if($vendorCredit->reason)
                                <x-detail-field :label="__('Reason')" :value="$vendorCredit->reason" class="col-span-3" />
                            @endif
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Line Items') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Expense Account') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th class="text-right">{{ __('Qty') }}</th>
                                        <th class="text-right">{{ __('Unit Price') }}</th>
                                        <th class="text-right">{{ __('Tax') }}</th>
                                        <th class="text-right">{{ __('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vendorCredit->lines as $line)
                                        <tr>
                                            <td>{{ $line->expenseAccount->name ?? '—' }}</td>
                                            <td>{{ $line->description }}</td>
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
                            <div class="balance-grid">
                                <div class="balance-row">
                                    <span class="balance-label">{{ __('Subtotal') }}:</span>
                                    <span class="balance-value">{{ format_money($vendorCredit->subtotal) }}</span>
                                </div>
                                <div class="balance-row">
                                    <span class="balance-label">{{ __('Tax') }}:</span>
                                    <span class="balance-value">{{ format_money($vendorCredit->tax_total) }}</span>
                                </div>
                                <div class="balance-total-row">
                                    <span class="balance-label">{{ __('Total') }}:</span>
                                    <span class="balance-value">{{ format_money($vendorCredit->total) }}</span>
                                </div>
                                <div class="balance-row">
                                    <span class="balance-label">{{ __('Applied') }}:</span>
                                    <span class="balance-value">{{ format_money($vendorCredit->amount_applied) }}</span>
                                </div>
                                <div class="balance-total-row">
                                    <span class="balance-label">{{ __('Available') }}:</span>
                                    <span class="balance-value">{{ format_money($vendorCredit->available) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.vendor-credits.index'), 'icon' => 'back', 'title' => __('Back to Vendor Credits')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>

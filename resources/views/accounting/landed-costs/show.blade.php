<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Landed Cost:') }} {{ $voucher->voucher_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    @if($voucher->status === 'draft')
                        <form method="POST" action="{{ route('accounting.landed-costs.post', $voucher) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Post this landed cost voucher?') }}')">
                            @csrf
                            <button type="submit" class="tr-save">{{ __('Post Voucher') }}</button>
                        </form>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                <a href="{{ route('accounting.landed-costs.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field :label="__('Voucher Number')" :value="$voucher->voucher_number" />
                            <x-detail-field :label="__('Date')" :value="$voucher->date->format('M d, Y')" />
                            <x-detail-field :label="__('Status')" noBorder>
                                @if($voucher->status === 'posted')
                                    <span class="status-pill positive">{{ __('Posted') }}</span>
                                @elseif($voucher->status === 'draft')
                                    <span class="status-pill neutral">{{ __('Draft') }}</span>
                                @else
                                    <span class="status-pill negative">{{ ucfirst($voucher->status) }}</span>
                                @endif
                            </x-detail-field>
                            <x-detail-field :label="__('Vendor')" :value="$voucher->vendor->name ?? 'N/A'" />
                            <x-detail-field :label="__('Allocation Method')" :value="str_replace('_', ' ', ucfirst($voucher->allocation_method))" />
                            <x-detail-field :label="__('Total Amount')" value-class="font-bold">
                                {{ format_money($voucher->total_amount) }}
                            </x-detail-field>
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Cost Components') }}</p>
                        <table class="record-datasheet">
                            <thead>
                                <tr>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="text-right">{{ __('Amount') }}</th>
                                    <th>{{ __('Payee Account') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($voucher->components as $component)
                                    <tr>
                                        <td>{{ ucfirst($component->component_type) }}</td>
                                        <td>{{ $component->description }}</td>
                                        <td class="text-right font-medium">{{ format_money($component->amount) }}</td>
                                        <td>{{ $component->payeeAccount->code ?? '' }} - {{ $component->payeeAccount->name ?? '' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 font-semibold">
                                    <td colspan="2" class="text-right">{{ __('Total') }}:</td>
                                    <td class="text-right">{{ format_money($voucher->total_amount) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Linked GRNs') }}</p>
                        <div class="space-y-2">
                            @foreach($voucher->grns as $grn)
                                <div class="flex items-center justify-between p-3 border rounded bg-gray-50 text-sm">
                                    <div><span class="font-medium">{{ $grn->grn_number }}</span> &mdash; {{ $grn->date->format('M d, Y') }}</div>
                                    <div class="text-right">{{ $grn->lines->count() }} {{ __('line(s)') }} &mdash; {{ format_money($grn->lines->sum('total_cost')) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($voucher->journalEntry)
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-5">{{ __('Journal Entry') }}: {{ $voucher->journalEntry->journal_number }}</p>
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Account') }}</th>
                                        <th class="text-right">{{ __('Debit') }} ({{ $cs }})</th>
                                        <th class="text-right">{{ __('Credit') }} ({{ $cs }})</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($voucher->journalEntry->lines as $line)
                                        <tr>
                                            <td>{{ $line->account->code ?? '' }} - {{ $line->account->name ?? '' }}</td>
                                            <td class="text-right text-sm text-gray-900">
                                                {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                            </td>
                                            <td class="text-right text-sm text-gray-900">
                                                {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.landed-costs.print', $voucher), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.landed-costs.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>

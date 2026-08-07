<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Settlement') }} – {{ $settlement->settlement_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Settlement Details --}}
                        <div class="card p-6">
                            <div class="form-section-label">1 · Settlement Details</div>

                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Status</dt>
                                    <dd>
                                        @if($settlement->status === 'posted')
                                            <span class="status-pill positive">Posted</span>
                                        @else
                                            <span class="status-pill neutral">Draft</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Payment Method</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $settlement->paymentMethod->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Bank Account</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $settlement->bankAccount->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Period</dt>
                                    <dd class="text-sm text-gray-900">{{ $settlement->period_start }} – {{ $settlement->period_end }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Reference</dt>
                                    <dd class="text-sm text-gray-900">{{ $settlement->reference ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Settled By</dt>
                                    <dd class="text-sm text-gray-900">{{ $settlement->settledBy->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Settled At</dt>
                                    <dd class="text-sm text-gray-900">{{ $settlement->settled_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                                </div>
                                @if($settlement->notes)
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Notes</dt>
                                        <dd class="text-sm text-gray-900">{{ $settlement->notes }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        {{-- Amounts --}}
                        <div class="card p-6">
                            <div class="form-section-label">2 · Amounts</div>

                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Total Settled</span>
                                    <span class="font-medium">@money($settlement->total_amount)</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Processing Fee</span>
                                    <span class="text-red-600">-@money($settlement->fee_amount)</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t pt-2">
                                    <span>Net Deposit</span>
                                    <span>@money($settlement->net_amount)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Journal Entry --}}
                    @if($settlement->journalEntry)
                        <div class="mt-6 card p-6">
                            <div class="form-section-label">3 · Journal Entry – {{ $settlement->journalEntry->reference }}</div>

                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-right">Debit ({{ $cs }})</th>
                                        <th class="text-right">Credit ({{ $cs }})</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($settlement->journalEntry->lines as $line)
                                        <tr>
                                            <td>
                                                {{ $line->account->code }} – {{ $line->account->name }}
                                            </td>
                                            <td class="numeric">
                                                {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                            </td>
                                            <td class="numeric">
                                                {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                            </td>
                                            <td>{{ $line->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('pos.settlements.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>

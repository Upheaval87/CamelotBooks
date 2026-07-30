<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('GRN') }} #{{ $grn->grn_number }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    @if($grn->status === 'draft')
                        @can('goods-received-notes.post')
                            <form method="POST" action="{{ route('accounting.goods-received-notes.post', $grn) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-save" onclick="return confirm('{{ __('Post this GRN? This will create inventory cost layers and a journal entry.') }}')">{{ __('Post') }}</button>
                            </form>
                        @endcan
                    @endif
                </div>

                <div class="tr-spacer"></div>

                <a href="{{ route('accounting.goods-received-notes.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
            @endif

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('GRN Number')" :value="$grn->grn_number" />
                    <x-detail-field :label="__('Status')">
                        @if($grn->status === 'posted')
                            <span class="status-pill positive">{{ __('Posted') }}</span>
                        @elseif($grn->status === 'draft')
                            <span class="status-pill neutral">{{ __('Draft') }}</span>
                        @else
                            <span class="status-pill negative">{{ ucfirst($grn->status) }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field :label="__('Date')" :value="$grn->date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Vendor')" :value="$grn->vendor->name ?? '—'" />
                    <x-detail-field :label="__('Purchase Order')">
                        @if($grn->purchaseOrder)
                            <a href="{{ route('accounting.purchase-orders.show', $grn->purchaseOrder) }}" class="text-ink hover:text-gold">{{ $grn->purchaseOrder->po_number }}</a>
                        @else
                            {{ '—' }}
                        @endif
                    </x-detail-field>
                    @if($grn->memo)
                        <x-detail-field :label="__('Description')" :value="$grn->memo" class="col-span-4" />
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Received Items') }}</p>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-right">{{ __('Qty Ordered') }}</th>
                                <th class="text-right">{{ __('Qty Received') }}</th>
                                <th class="text-right">{{ __('Unit Cost') }} ({{ $cs }})</th>
                                <th class="text-right">{{ __('Total Cost') }} ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grn->lines as $line)
                                <tr>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $line->description }}</td>
                                    <td class="numeric">{{ $line->quantity_ordered ?? '—' }}</td>
                                    <td class="numeric font-semibold">{{ $line->quantity_received }}</td>
                                    <td class="numeric">{{ format_number($line->unit_cost) }}</td>
                                    <td class="numeric font-semibold">{{ format_number($line->total_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="balance-grid">
                        <div class="balance-total-row">
                            <span class="balance-label">{{ __('Total Received') }}:</span>
                            <span class="balance-value">{{ format_number($grn->lines->sum('total_cost')) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($grn->journalEntry)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Journal Entry') }}</p>
                    <a href="{{ route('accounting.journal-entries.show', $grn->journalEntry) }}" class="text-ink hover:text-gold">
                        {{ $grn->journalEntry->reference }} — {{ __('View Journal Entry') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

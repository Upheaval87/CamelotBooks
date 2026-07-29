<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Till Session #') . $session->id }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-ink-soft">Session</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ $session->id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Terminal</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $session->terminal?->identifier ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Cashier</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $session->user?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        @if($session->isOpen())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Open</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Closed</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Opened At</p>
                        <p class="text-sm font-medium text-gray-900">{{ $session->opened_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Closed At</p>
                        <p class="text-sm font-medium text-gray-900">{{ $session->closed_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="card p-6 mb-6">
                <div class="form-section-label">1 · Cash Summary</div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Opening Float</p>
                        <p class="text-lg font-semibold text-gray-900">@money($session->opening_float)</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Expected Cash</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $session->expected_cash !== null ? format_money($session->expected_cash) : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Actual Cash Count</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $session->actual_cash_count !== null ? format_money($session->actual_cash_count) : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Variance</p>
                        <p class="text-lg font-semibold
                            {{ ($session->variance ?? 0) > 0 ? 'text-green-600' : (($session->variance ?? 0) < 0 ? 'text-red-600' : 'text-gray-900') }}">
                            {{ $session->variance !== null ? ($session->variance >= 0 ? '+' : '') . format_money($session->variance) : '—' }}
                        </p>
                    </div>
                </div>
            </div>

            @if($session->journalEntry)
                <div class="card p-6">
                    <div class="form-section-label">2 · Journal Entry</div>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th class="text-right">Debit ({{ $cs }})</th>
                                    <th class="text-right">Credit ({{ $cs }})</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($session->journalEntry->lines as $line)
                                    <tr>
                                        <td>
                                            {{ $line->account?->code }} – {{ $line->account?->name }}
                                        </td>
                                        <td class="numeric">
                                            {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                        </td>
                                        <td class="numeric">
                                            {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                        </td>
                                        <td class="text-ink-soft">{{ $line->description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('pos.till-sessions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Till Sessions</a>
            </div>
        </div>
    </div>
</x-app-layout>

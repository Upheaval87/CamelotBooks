<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">EIS Submission Status</h1>
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-1 block border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <x-primary-button type="submit">Filter</x-primary-button>
        </form>
    </div>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Receipt #</th>
                <th>Invoice Type</th>
                <th>Terminal</th>
                <th>Status</th>
                <th class="text-right">Retries</th>
                <th>Submitted At</th>
                <th>Error</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($submissions as $s)
                @php
                    $statusColor = match($s['status']) {
                        'accepted' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'error' => 'bg-red-100 text-red-800',
                        'submitted' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-mono">{{ $s['receipt_number'] }}</td>
                    <td>{{ strtoupper($s['invoice_type']) }}</td>
                    <td class="text-ink-soft">{{ $s['terminal_id'] ?? '—' }}</td>
                    <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColor }}">{{ ucfirst($s['status']) }}</span></td>
                    <td class="numeric">{{ $s['retry_count'] }}</td>
                    <td class="text-ink-soft">{{ $s['submitted_at'] ?? '—' }}</td>
                    <td class="px-4 py-2 text-sm text-red-600 text-xs max-w-xs truncate">{{ $s['error_message'] ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No submissions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>

<x-app-layout>
    <x-list-header title="{{ __('EIS Submissions') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Terminal</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Retries</th>
                                <th>Submitted</th>
                                <th>Error</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($submissions as $submission)
                                <tr>
                                    <td>{{ $submission->receipt_number }}</td>
                                    <td class="text-ink-soft">{{ $submission->terminal->site_id ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="status-pill {{ $submission->invoice_type === 'B2B' ? 'positive' : 'negative' }}">
                                            {{ $submission->invoice_type }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($submission->status === 'accepted')
                                            <span class="status-pill positive">Accepted</span>
                                        @elseif($submission->status === 'pending' || $submission->status === 'submitted')
                                            <span class="status-pill negative">{{ ucfirst($submission->status) }}</span>
                                        @elseif($submission->status === 'rejected')
                                            <span class="status-pill negative">Rejected</span>
                                        @else
                                            <span class="status-pill negative">Error</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-ink-soft">{{ $submission->retry_count }}</td>
                                    <td class="text-ink-soft">
                                        {{ $submission->submitted_at ? $submission->submitted_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="text-red-600 max-w-xs truncate">
                                        {{ $submission->error_message ?? '-' }}
                                    </td>
                                    <td class="text-right">
                                        @if($submission->status === 'error' && $submission->retry_count < 5)
                                            <form method="POST" action="{{ route('pos.eis.submissions.retry', $submission) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">Retry</button>
                                            </form>
                                        @endif
                                        @if($submission->validation_url)
                                            <a href="{{ $submission->validation_url }}" target="_blank" class="ml-2 text-blue-600 hover:text-blue-900 text-xs font-medium">Validate</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-ink-soft text-center">
                                        No EIS submissions yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t">
                    {{ $submissions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

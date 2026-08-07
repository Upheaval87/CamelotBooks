<x-app-layout>
    <x-list-header title="{{ __('EIS Terminals') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            

            <div class="card p-6">
                <div class="form-section-label">1 · Register New Terminal</div>
                <form method="POST" action="{{ route('pos.eis.terminals.store') }}">
                    @csrf
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="site_id" value="{{ __('Site ID') }}" />
                            <x-text-input id="site_id" name="site_id" type="text" class="mt-1 block w-full" :value="old('site_id')" required />
                            <x-input-error :messages="$errors->get('site_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="device_serial" value="{{ __('Device Serial (optional)') }}" />
                            <x-text-input id="device_serial" name="device_serial" type="text" class="mt-1 block w-full" :value="old('device_serial')" />
                        </div>
                        <div class="flex items-end">
                            <x-button variant="primary" type="submit">Register Terminal</x-button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Site ID</th>
                                <th>Serial</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Submissions</th>
                                <th class="text-center">Blocked</th>
                                <th>Last Submission</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($terminals as $terminal)
                                <tr>
                                    <td>{{ $terminal->site_id }}</td>
                                    <td class="text-ink-soft">{{ $terminal->device_serial ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($terminal->status === 'active')
                                            <span class="status-pill positive">Active</span>
                                        @elseif($terminal->status === 'pending')
                                            <span class="status-pill negative">Pending</span>
                                        @else
                                            <span class="status-pill negative">{{ ucfirst($terminal->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-ink-soft">{{ $terminal->submissions_count }}</td>
                                    <td class="text-center">
                                        @if($terminal->should_block_terminal)
                                            <span class="status-pill negative">YES</span>
                                        @else
                                            <span class="text-xs text-gray-400">No</span>
                                        @endif
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $terminal->last_submission_at ? $terminal->last_submission_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="text-right">
                                        @if($terminal->status === 'pending')
                                            <form method="POST" action="{{ route('pos.eis.terminals.activate', $terminal) }}" class="inline-flex items-center gap-2">
                                                @csrf
                                                <input type="text" name="tac" placeholder="Enter TAC" required
                                                    class="input w-32">
                                                <button type="submit" class="text-green-600 hover:text-green-900 text-xs font-medium">Activate</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-ink-soft text-center">
                                        No terminals registered. Register one above.
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

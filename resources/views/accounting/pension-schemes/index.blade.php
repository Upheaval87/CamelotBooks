<x-app-layout>
    <x-list-header title="{{ __('Create Scheme') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.pension-schemes.create') }}">
                    {{ __('Create Scheme') }}
                </x-button>
            </div>
            <div class="datasheet-wrap">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Registration No</th>
                                    <th>Employee Rate</th>
                                    <th>Employer Rate</th>
                                    <th>Max Contributory Salary</th>
                                    <th>Effective From</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($schemes as $scheme)
                                    <tr>
                                        <td>
                                            {{ $scheme->name }}
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $scheme->registration_number }}
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $scheme->employee_rate }}%
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $scheme->employer_rate }}%
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $scheme->max_contributory_salary ? format_money($scheme->max_contributory_salary) : '—' }}
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ \Carbon\Carbon::parse($scheme->effective_from)->format('d M Y') }}
                                        </td>
                                        <td class="text-center">
                                            @if ($scheme->is_current)
                                                <span class="status-pill positive">Current</span>
                                            @else
                                                <span class="status-pill negative">Expired</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('accounting.pension-schemes.show', $scheme) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                            <a href="{{ route('accounting.pension-schemes.edit', $scheme) }}" class="text-ink hover:text-gold">Edit</a>
                                            <form action="{{ route('accounting.pension-schemes.toggle', $scheme) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-{{ $scheme->is_current ? 'red' : 'green' }}-600 hover:text-{{ $scheme->is_current ? 'red' : 'green' }}-900">
                                                    {{ $scheme->is_current ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-ink-soft text-center">
                                            No pension schemes found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $schemes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

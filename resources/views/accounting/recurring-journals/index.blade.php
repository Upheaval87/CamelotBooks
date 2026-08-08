<x-app-layout>
    <x-list-header title="{{ __('New Recurring Template') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.recurring-journals.create') }}">
                    {{ __('New Recurring Template') }}
                </x-button>
            </div>
            

            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Frequency</th>
                                <th>Branch</th>
                                <th>Next Run</th>
                                <th>End Date</th>
                                <th class="text-center">Auto Post</th>
                                <th class="text-center">Status</th>
                                <th>Created By</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.recurring-journals.show', $template) }}" class="text-ink hover:text-gold">
                                            {{ $template->name }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $template->frequency }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $template->branch->name ?? '—' }}
                                    </td>
                                    <td>
                                        {{ $template->next_run_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td>
                                        {{ $template->end_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if($template->auto_post)
                                            <span class="status-pill positive">Yes</span>
                                        @else
                                            <span class="status-pill neutral">No</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($template->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill negative">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $template->createdBy->name ?? '—' }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.recurring-journals.edit', $template) }}" class="text-ink hover:text-gold">Edit</a>
                                        <form method="POST" action="{{ route('accounting.recurring-journals.toggle', $template) }}" class="inline" onsubmit="return fbConfirmSubmit(event, 'Are you sure you want to toggle this template?', { type: 'action' });">
                                            @csrf
                                            <button type="submit" class="{{ $template->is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' }}">
                                                {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-ink-soft">
                                        No recurring journal templates found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $templates->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

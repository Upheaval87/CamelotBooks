<x-app-layout>
    <x-list-header title="{{ __('Create Budget') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.budgets.create') }}">
                    {{ __('Create Budget') }}
                </x-button>
            </div>
            

            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Fiscal Year</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($budgets as $budget)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.budgets.show', $budget) }}" class="text-ink hover:text-gold">
                                            {{ $budget->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $budget->fiscalYear->label ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if($budget->status === 'draft')
                                            <span class="status-pill neutral">Draft</span>
                                        @else
                                            <span class="status-pill positive">Approved</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.budgets.show', $budget) }}" class="text-ink hover:text-gold">View</a>
                                        @if($budget->status === 'draft')
                                            <a href="{{ route('accounting.budgets.edit', $budget) }}" class="text-ink hover:text-gold">Edit</a>
                                        @endif
                                        <a href="{{ route('accounting.budgets.variance', $budget) }}" class="text-ink hover:text-gold">Variance</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">
                                        No budgets found.
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

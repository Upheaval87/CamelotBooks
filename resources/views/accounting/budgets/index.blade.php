<x-app-layout>
    <x-slot name="header">{{ __('Create Budget') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.budgets.create') }}">
                    {{ __('Create Budget') }}
                </x-button>
            </div>
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

<x-app-layout>
    <x-slot name="header">{{ __('Budget') }}: {{ $budget->name }}</x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <x-toolbar class="mb-6">
            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Record</span>
            <x-toolbar-button title="{{ __('New') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </x-toolbar-button>
            @if($budget->status === 'draft')
                <x-toolbar-button variant="commit" href="{{ route('accounting.budgets.edit', $budget) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save
                </x-toolbar-button>
            @endif

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Reference</span>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Copy from Prior Year
            </x-toolbar-button>
            <x-toolbar-button href="{{ route('accounting.budgets.variance', $budget) }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Compare to Actuals
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Document</span>
            <x-toolbar-button onclick="window.print()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <x-dropdown align="left" width="56">
                <x-slot name="trigger">
                    <x-toolbar-button>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </x-toolbar-button>
                </x-slot>
                <x-slot name="content">
                    <div class="py-1">
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Duplicate
                        </button>
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Import from Spreadsheet
                        </button>
                        <a href="{{ route('accounting.budgets.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Budgets
                        </a>
                    </div>
                </x-slot>
            </x-dropdown>

            <x-slot name="right">
                <x-toolbar-button variant="commit" onclick="if(confirm('Lock this budget? This will prevent further edits.')){}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Lock Budget
                </x-toolbar-button>
            </x-slot>
        </x-toolbar>
    </div>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Budget Overview') }}</h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Fiscal Year') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $budget->fiscalYear->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        @if($budget->status === 'draft')
                            <span class="status-pill neutral">Draft</span>
                        @elseif($budget->status === 'approved')
                            <span class="status-pill positive">Approved</span>
                        @elseif($budget->status === 'locked')
                            <span class="status-pill neutral">Locked</span>
                        @else
                            <span class="status-pill neutral">{{ ucfirst($budget->status) }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Total Budget') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">{{ format_money($budget->total_amount ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Total Actual') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">{{ format_money($budget->total_actual ?? 0) }}</dd>
                </div>
                @if($budget->description)
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $budget->description }}</dd>
                    </div>
                @endif
            </div>
        </div>

        @if(isset($budget->lines) && $budget->lines->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Budget Lines') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Budgeted</th>
                                <th class="text-right">Actual</th>
                                <th class="text-right">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($budget->lines as $line)
                                <tr>
                                    <td>{{ $line->account->name ?? '—' }}</td>
                                    <td class="numeric">{{ format_money($line->budgeted_amount) }}</td>
                                    <td class="numeric">{{ format_money($line->actual_amount ?? 0) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ ($line->actual_amount - $line->budgeted_amount) > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ format_money(($line->actual_amount ?? 0) - $line->budgeted_amount) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

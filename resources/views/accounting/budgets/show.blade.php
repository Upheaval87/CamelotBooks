<x-app-layout>
    <x-slot name="header">{{ __('Budget') }}: {{ $budget->name }}</x-slot>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Record') }}</span>
                    <a href="{{ route('accounting.budgets.create') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('New') }}
                    </a>
                    @if($budget->status === 'draft')
                        <a href="{{ route('accounting.budgets.edit', $budget) }}" class="tr-save">{{ __('Save') }}</a>
                    @endif
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reference') }}</span>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        {{ __('Copy from Prior Year') }}
                    </button>
                    <a href="{{ route('accounting.budgets.variance', $budget) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        {{ __('Compare to Actuals') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Document') }}</span>
                    <button onclick="window.print()" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </button>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('Export') }}
                    </button>
                </div>

                <div class="tr-spacer"></div>

                <button type="button" class="tr-archive" onclick="if(confirm('{{ __('Lock this budget?') }}')){}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    {{ __('Lock Budget') }}
                </button>

                <x-dropdown align="left" width="56">
                    <x-slot name="trigger">
                        <button type="button" class="tr-more">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                {{ __('Duplicate') }}
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                {{ __('Import from Spreadsheet') }}
                            </button>
                            <a href="{{ route('accounting.budgets.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                {{ __('Back to Budgets') }}
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Budget Overview') }}</p>
                <div class="detail-grid">
                    <x-detail-field :label="__('Fiscal Year')" :value="$budget->fiscalYear->name ?? '—'" />
                    <x-detail-field :label="__('Status')" noBorder>
                        @if($budget->status === 'draft')
                            <span class="status-pill neutral">{{ __('Draft') }}</span>
                        @elseif($budget->status === 'approved')
                            <span class="status-pill positive">{{ __('Approved') }}</span>
                        @elseif($budget->status === 'locked')
                            <span class="status-pill neutral">{{ __('Locked') }}</span>
                        @else
                            <span class="status-pill neutral">{{ ucfirst($budget->status) }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field :label="__('Total Budget')" value-class="text-lg font-bold text-ink">
                        {{ format_money($budget->total_amount ?? 0) }}
                    </x-detail-field>
                    <x-detail-field :label="__('Total Actual')" value-class="text-lg font-bold text-ink">
                        {{ format_money($budget->total_actual ?? 0) }}
                    </x-detail-field>
                    @if($budget->description)
                        <x-detail-field :label="__('Description')" :value="$budget->description" class="col-span-3" />
                    @endif
                </div>
            </div>

            @if(isset($budget->lines) && $budget->lines->count() > 0)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Budget Lines') }}</p>
                    <div class="overflow-x-auto">
                        <table class="record-datasheet">
                            <thead>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <th class="text-right">{{ __('Budgeted') }}</th>
                                    <th class="text-right">{{ __('Actual') }}</th>
                                    <th class="text-right">{{ __('Variance') }}</th>
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
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.budgets.index'), 'icon' => 'back', 'title' => __('Back to Budgets')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>

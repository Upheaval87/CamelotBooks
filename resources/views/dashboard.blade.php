<x-app-layout>
    <x-slot name="header">
        {{ __('Dashboard') }}
    </x-slot>

    <div class="space-y-6">
        {{-- KPI Cards --}}
        <div class="grid-stats">
            <div class="kpi-card animate-fade-in-up" style="animation-delay: 0ms">
                <p class="kpi-label">Total Revenue</p>
                <p class="kpi-value">{{ format_money($totalRevenue ?? 0) }}</p>
                <p class="kpi-trend text-success">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    <span>12.5% vs last month</span>
                </p>
            </div>
            <div class="kpi-card animate-fade-in-up" style="animation-delay: 50ms">
                <p class="kpi-label">Total Expenses</p>
                <p class="kpi-value">{{ format_money($totalExpenses ?? 0) }}</p>
                <p class="kpi-trend text-danger">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    <span>3.2% vs last month</span>
                </p>
            </div>
            <div class="kpi-card animate-fade-in-up" style="animation-delay: 100ms">
                <p class="kpi-label">Outstanding Invoices</p>
                <p class="kpi-value">{{ format_money($outstandingInvoices ?? 0) }}</p>
                <p class="kpi-trend text-neutral-400">
                    <span>{{ $pendingCount ?? 0 }} unpaid invoices</span>
                </p>
            </div>
            <div class="kpi-card animate-fade-in-up" style="animation-delay: 150ms">
                <p class="kpi-label">Cash Balance</p>
                <p class="kpi-value">{{ format_money($cashBalance ?? 0) }}</p>
                <p class="kpi-trend text-success">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    <span>{{ $bankAccounts ?? 0 }} bank accounts</span>
                </p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card animate-fade-in-up" style="animation-delay: 200ms">
            <div class="card-header">
                <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('accounting.invoices.create') }}" class="btn-primary btn-md">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Invoice
                    </a>
                    <a href="{{ route('accounting.bills.create') }}" class="btn-secondary btn-md">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        New Bill
                    </a>
                    <a href="{{ route('accounting.journal-entries.create') }}" class="btn-secondary btn-md">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Journal Entry
                    </a>
                    <a href="{{ route('accounting.bank-reconciliation.index', $defaultBankAccountId ?? 1) }}" class="btn-secondary btn-md">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Reconcile
                    </a>
                </div>
            </div>
        </div>

        {{-- My Tasks --}}
        <div class="card animate-fade-in-up" style="animation-delay: 250ms">
            <div class="card-header flex items-center justify-between">
                <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">My Tasks</h3>
                <a href="{{ route('todo.index') }}" class="text-xs font-medium text-accent hover:text-accent-hover transition-colors">Open tasks</a>
            </div>
            <div class="card-body">
                @if($todoOverdue === 0 && $todoToday === 0 && $myTasks->isEmpty())
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <p class="empty-state-title">No active tasks</p>
                        <p class="empty-state-text">Add a task to keep track of what needs doing.</p>
                    </div>
                @else
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-danger tabular-nums">{{ $todoOverdue }}</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Overdue</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-warning tabular-nums">{{ $todoToday }}</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Due today</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-neutral-800 tabular-nums">{{ $myTasks->count() }}</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Active</p>
                        </div>
                        <div class="flex-1 min-w-[220px]">
                            <ul class="space-y-2">
                                @foreach($myTasks->take(4) as $task)
                                    <li class="flex items-center gap-2 text-sm">
                                        <span class="w-2 h-2 rounded-full shrink-0 {{ $task->priority === 'high' ? 'bg-danger' : ($task->priority === 'medium' ? 'bg-warning' : 'bg-neutral-300') }}"></span>
                                        <span class="flex-1 truncate {{ $task->isOverdue() ? 'text-danger' : 'text-neutral-800' }}">{{ $task->title }}</span>
                                        <span class="text-xs text-neutral-400 shrink-0">{{ $task->deadlineLabel() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="card animate-fade-in-up" style="animation-delay: 300ms">
            <div class="card-header flex items-center justify-between">
                <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">Recent Activity</h3>
                <a href="{{ route('admin.audit-log.index') }}" class="text-xs font-medium text-accent hover:text-accent-hover transition-colors">View all</a>
            </div>
            <div class="card-body">
                @if(isset($recentActivities) && count($recentActivities) > 0)
                    <div class="timeline">
                        @foreach($recentActivities as $activity)
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <p class="font-medium text-neutral-800 dark:text-neutral-200">{{ $activity->description ?? 'Activity' }}</p>
                                    <p class="text-xs text-neutral-400 mt-0.5">{{ $activity->created_at?->diffForHumans() ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="empty-state-title">No recent activity</p>
                        <p class="empty-state-text">Activity will appear here as you use the system.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

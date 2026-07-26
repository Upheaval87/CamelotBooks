<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Budget') }}: {{ $budget->name }}
            </h2>
            <div class="flex items-center space-x-3">
                @if($budget->status === 'draft')
                    <form method="POST" action="{{ route('accounting.budgets.approve', $budget) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Approve') }}
                        </button>
                    </form>
                    <a href="{{ route('accounting.budgets.edit', $budget) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Edit') }}
                    </a>
                @endif
                <a href="{{ route('accounting.budgets.variance', $budget) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('View Variance') }}
                </a>
                <a href="{{ route('accounting.budgets.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Budgets') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Budget Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $budget->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($budget->status === 'draft')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fiscal Year') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $budget->fiscalYear->label ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $budget->creator->name ?? '—' }}</dd>
                    </div>
                    @if($budget->description)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $budget->description }}</dd>
                        </div>
                    @endif
                    @if($budget->approved_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Approved By') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $budget->approver->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Approved At') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $budget->approved_at?->format('M d, Y H:i') ?? '—' }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            @php
                $linesByAccount = $budget->lines->groupBy('account_id');
                $periods = $budget->lines->pluck('period_label')->unique()->sort()->values();
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Budget Lines') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                @foreach($periods as $period)
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $period }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($linesByAccount as $accountId => $accountLines)
                                @php $account = $accountLines->first()->account; @endphp
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $account->code }} - {{ $account->name }}
                                    </td>
                                    @foreach($periods as $period)
                                        @php $line = $accountLines->where('period_label', $period)->first(); @endphp
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                            {{ $line ? format_money($line->amount) : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                        {{ format_money($accountLines->sum('amount')) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($periods) + 2 }}" class="px-4 py-4 text-center text-sm text-gray-500">
                                        No budget lines defined.
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

<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Branch Requests') }}" description="{{ __('Requests for extra branch capacity, with quotations and payments.') }}" />

        

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <x-superadmin.card>
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('Company') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Branch') }}</x-superadmin.th>
                            <x-superadmin.th align="right">{{ __('Qty') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Requested') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Quotation') }}</x-superadmin.th>
                            <x-superadmin.th align="center">{{ __('Status') }}</x-superadmin.th>
                            <x-superadmin.th align="center">{{ __('Actions') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @php
                            $pillMap = [
                                \App\Models\BranchRequest::STATUS_PENDING_REVIEW => 'warning',
                                \App\Models\BranchRequest::STATUS_QUOTED => 'accent',
                                \App\Models\BranchRequest::STATUS_AWAITING_PAYMENT => 'warning',
                                \App\Models\BranchRequest::STATUS_APPROVED => 'active',
                                \App\Models\BranchRequest::STATUS_REJECTED => 'danger',
                                \App\Models\BranchRequest::STATUS_EXPIRED => 'muted',
                                \App\Models\BranchRequest::STATUS_CANCELLED => 'muted',
                            ];
                        @endphp
                        @forelse($requests as $r)
                            @php $label = $r->statusLabel(); @endphp
                            <tr>
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="{{ route('superadmin.companies.branch-requests.show', [$r->company_id, $r->id]) }}" class="font-bold text-gray-900">{{ $r->company_name ?? ($r->company?->name ?? '—') }}</a>
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    <span class="text-gray-600">{{ $r->branch_name }}</span>
                                    @if($r->branch_code)
                                        <span class="mt-1 block text-[12.5px] text-gray-400">{{ $r->branch_code }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] text-right align-middle font-semibold tabular-nums text-gray-900">{{ $r->requested_quantity }}</td>
                                <td class="px-5 py-[18px] align-middle text-gray-500">{{ $r->requested_at?->format('M j, Y') }}</td>
                                <td class="px-5 py-[18px] align-middle">
                                    @if($r->quotation)
                                        <code class="rounded-md border border-slate-200 bg-slate-100 px-2 py-[3px] font-mono text-xs text-slate-600">{{ $r->quotation->quotation_number }}</code>
                                        <span class="mt-1 block text-[12.5px] text-gray-400">{{ number_format($r->quotation->total, 2) }} {{ $r->quotation->currency_code }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] text-center align-middle">
                                    <x-superadmin.badge :variant="$pillMap[$r->status] ?? 'muted'">{{ $label }}</x-superadmin.badge>
                                </td>
                                <td class="px-5 py-[18px] text-center align-middle">
                                    <a href="{{ route('superadmin.companies.branch-requests.show', [$r->company_id, $r->id]) }}" class="inline-flex items-center gap-1.5 rounded-[10px] border border-gold-600/35 bg-gradient-to-b from-[#fffdf8] to-[#f7f0df] px-4 py-2 text-[13px] font-bold text-gold-700 shadow-edit transition hover:-translate-y-px hover:border-gold-600/55 hover:text-gold-800 hover:shadow-edit-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        {{ __('Review') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No branch requests found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-superadmin.card>
    </x-superadmin.layout>
</x-app-layout>

<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Period Lock Status" />
    @forelse($fiscal_years as $fy)
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-gray-900">{{ $fy['label'] }}</span>
                <span class="ml-4 text-sm text-gray-500">{{ $fy['start_date'] }} to {{ $fy['end_date'] }}</span>
            </div>
            <div>
                @if($fy['status'] === 'closed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Locked</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Open</span>
                @endif
            </div>
        </div>
        <table class="datasheet">
            <thead class="bg-white"><tr>
                <th>Label</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($fy['periods'] as $period)
                <tr class="hover:bg-gray-50">
                    <td>{{ $period['label'] }}</td>
                    <td>{{ $period['start_date'] }}</td>
                    <td>{{ $period['end_date'] }}</td>
                    <td>
                        @if($period['status'] === 'locked')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Locked</span>
                        @elseif($period['status'] === 'closed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Closed</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Open</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
        <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
            <p class="text-sm text-gray-500">No fiscal years found.</p>
        </div>
    @endforelse
</div>
</x-app-layout>
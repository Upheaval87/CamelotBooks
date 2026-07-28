<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Period Lock Status</h1>
    @forelse($fiscalYears as $fy)
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-gray-900">{{ $fy->label }}</span>
                <span class="ml-4 text-sm text-gray-500">{{ $fy->start_date }} to {{ $fy->end_date }}</span>
            </div>
            <div>
                @if($fy->is_locked)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Locked</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Open</span>
                @endif
            </div>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white"><tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($fy->periods as $period)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $period->label }}</td>
                    <td class="px-4 py-2 text-sm">{{ $period->start_date }}</td>
                    <td class="px-4 py-2 text-sm">{{ $period->end_date }}</td>
                    <td class="px-4 py-2 text-sm">
                        @if($period->is_locked)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Locked</span>
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
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Period Lock Status</h1>
    @forelse($fiscalYears as $fy)
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-gray-900">{{ $fy->label }}</span>
                <span class="ml-4 text-sm text-gray-500">{{ $fy->start_date }} to {{ $fy->end_date }}</span>
            </div>
            <div>
                @if($fy->is_locked)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Locked</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Open</span>
                @endif
            </div>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white"><tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($fy->periods as $period)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $period->label }}</td>
                    <td class="px-4 py-2 text-sm">{{ $period->start_date }}</td>
                    <td class="px-4 py-2 text-sm">{{ $period->end_date }}</td>
                    <td class="px-4 py-2 text-sm">
                        @if($period->is_locked)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Locked</span>
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
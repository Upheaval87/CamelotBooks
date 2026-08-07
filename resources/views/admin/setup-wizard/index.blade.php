<x-app-layout>
    <x-list-header title="{{ __('Setup Wizard') }}" />

<div class="py-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Setup Wizard — {{ $company->name }}</h1>

        

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="text-2xl font-bold text-gray-900">{{ $completedCount }}/{{ count($steps) }}</div>
                <div class="text-sm text-gray-500">setup steps completed</div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ ($completedCount / count($steps)) * 100 }}%"></div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Checklist</h2>
            <div class="space-y-3">
                @foreach($steps as $step)
                <div class="flex items-center justify-between p-3 rounded-md {{ $step['done'] ? 'bg-green-50' : 'bg-gray-50' }}">
                    <div class="flex items-center gap-3">
                        @if($step['done'])
                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 text-green-600">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                        @else
                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-gray-200 text-gray-500">
                                <span class="text-xs font-medium">{{ $loop->iteration }}</span>
                            </span>
                        @endif
                        <span class="text-sm font-medium {{ $step['done'] ? 'text-green-800' : 'text-gray-900' }}">{{ $step['label'] }}</span>
                    </div>
                    @if(!$step['done'])
                        <a href="{{ route($step['route']) }}" class="text-sm text-gold-700 hover:text-gold-800 font-medium">Set Up →</a>
                    @else
                        <span class="text-xs text-green-600 font-medium">Done</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        @if(!$hasBranch || !$hasCostCenter)
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Setup</h2>
            <p class="text-sm text-gray-500 mb-4">Create your first branch and cost center in one step.</p>
            <form method="POST" action="{{ route('admin.setup-wizard.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Branch Name</label>
                        <input type="text" name="branch_name" value="{{ old('branch_name', 'Main Branch') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Branch Code</label>
                        <input type="text" name="branch_code" value="{{ old('branch_code', 'MBR') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Cost Center Name</label>
                        <input type="text" name="cost_center_name" value="{{ old('cost_center_name', 'General') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cost Center Code</label>
                        <input type="text" name="cost_center_code" value="{{ old('cost_center_code', 'GEN') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">
                        Create & Continue
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
</x-app-layout>

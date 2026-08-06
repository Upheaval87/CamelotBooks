<x-app-layout>
    <div class="list-header">
        <div>
            <h1 class="font-sans italic font-semibold tracking-tight text-ink text-[1.125rem] lg:text-[1.375rem]">{{ __('Companies') }}</h1>
        </div>
        <button onclick="document.getElementById('create-company-modal').classList.remove('hidden')" class="list-header-create">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('Create Company') }}
        </button>
    </div>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($companies as $entry)
                    @php($company = $entry['company'])
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">{{ $company->name }}</h3>
                            @if(session('current_company_id') == $company->id)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Current</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-600 space-y-1 mb-4">
                            @if($company->legal_name)
                                <p><span class="text-gray-500">Legal:</span> {{ $company->legal_name }}</p>
                            @endif
                            @if($company->company_code)
                                <p><span class="text-gray-500">Code:</span> {{ $company->company_code }}</p>
                            @endif
                            <p><span class="text-gray-500">Currency:</span> {{ $company->base_currency }}</p>
                            <p><span class="text-gray-500">Role:</span> {{ $entry['role'] ?? '—' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(session('current_company_id') != $company->id)
                                <form method="POST" action="{{ route('companies.select', $company->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Switch To') }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 border border-transparent rounded-md font-semibold text-xs text-indigo-700 uppercase tracking-widest transition ease-in-out duration-150">
                                    {{ __('Go to Dashboard') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                        No companies found. Create one to get started.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div id="create-company-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" onclick="document.getElementById('create-company-modal').classList.add('hidden')">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="{{ route('companies.store') }}">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Create Company') }}</h3>
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="comp_name" value="{{ __('Company Name') }}" />
                                <x-text-input id="comp_name" name="name" type="text" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="company_code" value="{{ __('Company Code') }}" />
                                <x-text-input id="company_code" name="company_code" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="base_currency" value="{{ __('Base Currency') }}" />
                                <select id="base_currency" name="base_currency" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    @forelse($currencies as $currency)
                                        <option value="{{ $currency->code }}">{{ $currency->label() }}</option>
                                    @empty
                                        <option value="MWK">MWK - Malawian Kwacha</option>
                                    @endforelse
                                </select>
                            </div>
                            <div>
                                <x-input-label for="fiscal_year_start_month" value="{{ __('Fiscal Year Start Month') }}" />
                                <select id="fiscal_year_start_month" name="fiscal_year_start_month" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}">{{ Carbon\Carbon::create()->month($m)->format('F') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <x-primary-button type="submit">{{ __('Create') }}</x-primary-button>
                        <button type="button" onclick="document.getElementById('create-company-modal').classList.add('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

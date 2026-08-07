<x-app-layout>
    <x-list-header title="{{ __('Super Admin Panel') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <p class="text-sm text-gray-600">
                    {{ __('This panel is a Phase 4 placeholder. From here you can enter one of your assigned companies below — each entry is logged as support access.') }}
                </p>

                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-ink mb-3">{{ __('Your Companies') }}</h3>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse($user->accessibleCompanies() as $company)
                            <form method="POST" action="{{ route('companies.select', $company->id) }}" class="block">
                                @csrf
                                <button type="submit" class="w-full text-left bg-white border border-line rounded-lg p-4 hover:border-gold hover:shadow-sm transition">
                                    <span class="block font-medium text-ink">{{ $company->name }}</span>
                                    <span class="block text-xs text-gray-500 mt-1">
                                        {{ $company->company_code }} · {{ $company->base_currency }}
                                    </span>
                                </button>
                            </form>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No companies assigned yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('POS Terminal') }} — {{ session('pos_terminal_identifier') }}
            </h2>
            <div class="flex items-center gap-3 text-sm text-gray-600">
                <span>Cashier: <strong>{{ session('pos_cashier_name') }}</strong></span>
                <form method="POST" action="{{ route('pos.cashier.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                        End Session
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600">POS terminal dashboard — coming in Stage 2 (Till Sessions) and Stage 3 (Checkout).</p>
            </div>
        </div>
    </div>
</x-app-layout>

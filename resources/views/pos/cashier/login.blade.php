<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - Cashier Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .pin-input { letter-spacing: 0.5em; text-align: center; font-size: 1.5rem; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Point of Sale</h1>
                <p class="text-sm text-gray-500 mt-1">Enter your PIN to start</p>
            </div>

            

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('pos.cashier.login.post') }}">
                @csrf

                <div class="mb-4">
                    <label for="terminal_id" class="block text-sm font-medium text-gray-700 mb-1">Terminal</label>
                    <select id="terminal_id" name="terminal_id" required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-gold-500 focus:ring-gold-500 text-sm">
                        <option value="">Select terminal...</option>
                        @foreach($terminals as $terminal)
                            <option value="{{ $terminal->id }}" {{ old('terminal_id') == $terminal->id ? 'selected' : '' }}>
                                {{ $terminal->identifier }} — {{ $terminal->name }}
                                @if($terminal->branch) ({{ $terminal->branch->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label for="pin" class="block text-sm font-medium text-gray-700 mb-1">PIN</label>
                    <input type="password" id="pin" name="pin" required
                        maxlength="10" minlength="4" autocomplete="off"
                        class="pin-input w-full border-gray-300 rounded-md shadow-sm focus:border-gold-500 focus:ring-gold-500 text-sm"
                        placeholder="••••" autofocus>
                </div>

                <button type="submit"
                    class="w-full bg-gold-600 text-white py-3 rounded-md font-semibold hover:bg-gold-800 transition text-sm">
                    Start Session
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('dashboard') }}" class="text-xs text-gray-400 hover:text-gray-600">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Sign in to POS') }} — {{ config('app.name', 'CamelotBooks') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pos-auth-body" x-data="posLogin()">
    <div class="pos-auth-split">
        {{-- Brand panel (desktop only) --}}
        <aside class="pos-auth-brand">
            <div class="pos-auth-brand-top">
                <span class="pos-auth-logo">CB</span>
                <div>
                    <div class="pos-auth-wordmark">CamelotBooks</div>
                    <div class="pos-auth-tagline">Enterprise Accounting & Advisory Services</div>
                </div>
            </div>
            <h1>Point of Sale</h1>
            <p class="pos-auth-lede">Fast, reliable checkout for your retail operations — with real-time inventory, receipt printing, and complete audit trails.</p>
            <div class="pos-auth-bullets">
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">✓</span> Cash, card & mobile payments</div>
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">✓</span> Barcode scanning support</div>
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">✓</span> Real-time stock tracking</div>
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">✓</span> Offline-capable sales</div>
            </div>
            <p class="pos-auth-footer">www.camelotbooks.com · +265 1 234 567</p>
        </aside>

        {{-- Form panel --}}
        <main class="pos-auth-main">
            <div class="pos-auth-card">
                <h2 class="pos-auth-title">Sign in to POS</h2>

                {{-- Error --}}
                @if ($errors->any())
                    <div class="pos-auth-error">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="pos-auth-success">{{ session('status') }}</div>
                @endif

                {{-- Tabs --}}
                <div class="pos-auth-tabs">
                    <button type="button" class="pos-auth-tab" :class="tab === 'password' && 'active'" @click="tab = 'password'; errors = []">Password</button>
                    <button type="button" class="pos-auth-tab" :class="tab === 'pin' && 'active'" @click="tab = 'pin'; errors = []">Cashier PIN</button>
                </div>

                {{-- Password Tab --}}
                <div x-show="tab === 'password'" x-transition>
                    <form method="POST" action="{{ route('pos.login.post') }}">
                        @csrf
                        <input type="hidden" name="auth_type" value="password">

                        <div class="pos-auth-field">
                            <label class="pos-auth-label">Username or Email</label>
                            <input type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                class="pos-auth-input" placeholder="e.g. elvis@camelotbooks.com">
                        </div>

                        <div class="pos-auth-field">
                            <label class="pos-auth-label">Password</label>
                            <div class="pos-auth-pw-wrap">
                                <input :type="showPw ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                    class="pos-auth-input" placeholder="Enter password" style="padding-right:44px">
                                <button type="button" class="pos-auth-pw-toggle" @click="showPw = !showPw" :aria-label="showPw ? 'Hide password' : 'Show password'">
                                    <svg x-show="!showPw" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showPw" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="pos-auth-field">
                            <label class="pos-auth-label">Branch / Terminal</label>
                            <select name="terminal_id" required class="pos-auth-input">
                                <option value="">Select terminal…</option>
                                @foreach($terminals as $terminal)
                                    <option value="{{ $terminal->id }}" {{ old('terminal_id') == $terminal->id ? 'selected' : '' }}>
                                        {{ $terminal->identifier }} — {{ $terminal->branch?->name ?? 'No branch' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <label class="pos-auth-remember">
                            <input type="checkbox" name="remember" class="rounded">
                            <span>Remember me</span>
                        </label>

                        <button type="submit" class="pos-auth-submit">Sign In →</button>
                    </form>

                    <div class="pos-auth-links">
                        <a href="{{ route('pos.reset') }}">Forgot password?</a>
                    </div>
                </div>

                {{-- PIN Tab --}}
                <div x-show="tab === 'pin'" x-transition>
                    {{-- Cashier Picker --}}
                    <div class="pos-auth-field">
                        <label class="pos-auth-label">Select Cashier</label>
                        <div class="pos-auth-cashier-grid">
                            @foreach($cashiers as $cashier)
                                <button type="button" class="pos-auth-cashier-chip"
                                    :class="pinUser === {{ $cashier['id'] }} && 'active'"
                                    @click="pinUser = {{ $cashier['id'] }}; pinDigits = []; errors = []">
                                    <span class="pos-auth-cashier-avatar">{{ strtoupper(substr($cashier['name'], 0, 1)) }}</span>
                                    <span class="pos-auth-cashier-name">{{ $cashier['name'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- PIN Dots --}}
                    <div class="pos-auth-pin-dots" x-show="pinUser">
                        <template x-for="i in 4" :key="i">
                            <span class="pos-auth-pin-dot" :class="pinDigits.length >= i && 'filled'"></span>
                        </template>
                    </div>

                    {{-- Keypad --}}
                    <div class="pos-auth-keypad" x-show="pinUser">
                        <template x-for="n in [1,2,3,4,5,6,7,8,9,'clear',0,'back']" :key="n">
                            <button type="button" class="pos-auth-key"
                                :class="n === 'clear' ? 'pos-auth-key--fn' : n === 'back' ? 'pos-auth-key--fn' : ''"
                                @click="pinKey(n)"
                                x-text="n === 'clear' ? 'Clear' : n === 'back' ? '⌫' : n"></button>
                        </template>
                    </div>

                    {{-- Hidden PIN input for form submission --}}
                    <form method="POST" action="{{ route('pos.login.post') }}" x-ref="pinForm">
                        @csrf
                        <input type="hidden" name="auth_type" value="pin">
                        <input type="hidden" name="pin" :value="pinDigits.join('')">
                        <input type="hidden" name="terminal_id" value="{{ old('terminal_id') }}">
                    </form>

                    <button type="button" class="pos-auth-submit pos-auth-submit--pin"
                        :disabled="!pinUser || pinDigits.length < 4"
                        @click="submitPin()">
                        Unlock Register
                    </button>

                    <div class="pos-auth-links">
                        <a href="{{ route('pos.reset') }}">Reset PIN</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function posLogin() {
            return {
                tab: '{{ old('auth_type') === 'pin' ? 'pin' : 'password' }}',
                showPw: false,
                pinUser: null,
                pinDigits: [],
                errors: [],

                pinKey(n) {
                    if (n === 'clear') { this.pinDigits = []; return; }
                    if (n === 'back') { this.pinDigits.pop(); return; }
                    if (this.pinDigits.length < 4) this.pinDigits.push(String(n));
                    if (this.pinDigits.length === 4) {
                        this.$nextTick(() => this.submitPin());
                    }
                },

                submitPin() {
                    if (this.pinDigits.length < 4) return;
                    this.$refs.pinForm.querySelector('input[name="terminal_id"]').value =
                        document.querySelector('select[name="terminal_id"]')?.value || '';
                    this.$refs.pinForm.submit();
                },
            };
        }
    </script>
</body>
</html>

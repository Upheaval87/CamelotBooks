<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Verify Identity') }} — {{ config('app.name', 'CamelotBooks') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pos-auth-body" x-data="posVerify()">
    <div class="pos-auth-split">
        {{-- Brand panel --}}
        <aside class="pos-auth-brand pos-auth-brand--verify">
            <div class="pos-auth-brand-top">
                <span class="pos-auth-logo">CB</span>
                <div>
                    <div class="pos-auth-wordmark">CamelotBooks</div>
                    <div class="pos-auth-tagline">Enterprise Accounting & Advisory Services</div>
                </div>
            </div>
            <h1>Identity verification</h1>
            <p class="pos-auth-lede">Enter your cashier PIN or password to authorize this sensitive action.</p>
            <div class="pos-auth-bullets">
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">🛡</span> Supervisory approval required</div>
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">📋</span> Action logged for audit</div>
            </div>
            <p class="pos-auth-footer">www.camelotbooks.com · +265 1 234 567</p>
        </aside>

        <main class="pos-auth-main">
            <div class="pos-auth-card">
                <h2 class="pos-auth-title">Verify identity</h2>
                <p class="pos-auth-subtitle">Enter your PIN or password to continue.</p>

                @if ($errors->any())
                    <div class="pos-auth-error">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                {{-- Tabs --}}
                <div class="pos-auth-tabs">
                    <button type="button" class="pos-auth-tab" :class="method === 'pin' && 'active'" @click="method = 'pin'">PIN</button>
                    <button type="button" class="pos-auth-tab" :class="method === 'password' && 'active'" @click="method = 'password'">Password</button>
                </div>

                {{-- PIN Verification --}}
                <div x-show="method === 'pin'" x-transition>
                    <div class="pos-auth-pin-dots">
                        <template x-for="i in 4" :key="i">
                            <span class="pos-auth-pin-dot" :class="pinDigits.length >= i && 'filled'"></span>
                        </template>
                    </div>

                    <div class="pos-auth-keypad">
                        <template x-for="n in [1,2,3,4,5,6,7,8,9,'clear',0,'back']" :key="'v'+n">
                            <button type="button" class="pos-auth-key"
                                :class="(n === 'clear' || n === 'back') ? 'pos-auth-key--fn' : ''"
                                @click="pinKey(n)"
                                x-text="n === 'clear' ? 'Clear' : n === 'back' ? '⌫' : n"></button>
                        </template>
                    </div>

                    <form method="POST" action="{{ route('pos.verify.post') }}" x-ref="pinForm" style="display:none">
                        @csrf
                        <input type="hidden" name="pin" :value="pinDigits.join('')">
                    </form>

                    <button type="button" class="pos-auth-submit pos-auth-submit--pin" :disabled="pinDigits.length < 4"
                        @click="$refs.pinForm.submit()">
                        Verify & continue
                    </button>
                </div>

                {{-- Password Verification --}}
                <div x-show="method === 'password'" x-transition>
                    <form method="POST" action="{{ route('pos.verify.password') }}">
                        @csrf
                        <div class="pos-auth-field">
                            <label class="pos-auth-label">Password</label>
                            <input type="password" name="password" required autofocus autocomplete="current-password"
                                class="pos-auth-input" placeholder="Enter your account password">
                        </div>
                        <button type="submit" class="pos-auth-submit">Verify & continue →</button>
                    </form>
                </div>

                <div class="pos-auth-links" style="margin-top:1.5rem">
                    <a href="{{ route('pos.dashboard') }}">Cancel — go back</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function posVerify() {
            return {
                method: 'pin',
                pinDigits: [],

                pinKey(n) {
                    if (n === 'clear') { this.pinDigits = []; return; }
                    if (n === 'back') { this.pinDigits.pop(); return; }
                    if (this.pinDigits.length < 4) this.pinDigits.push(String(n));
                    if (this.pinDigits.length === 4) {
                        this.$nextTick(() => this.$refs.pinForm.submit());
                    }
                },
            };
        }
    </script>
</body>
</html>

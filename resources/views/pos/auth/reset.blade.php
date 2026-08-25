<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Reset POS Access') }} — {{ config('app.name', 'CamelotBooks') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pos-auth-body" x-data="posReset()">
    <div class="pos-auth-split">
        {{-- Brand panel --}}
        <aside class="pos-auth-brand pos-auth-brand--reset">
            <div class="pos-auth-brand-top">
                <span class="pos-auth-logo">CB</span>
                <div>
                    <div class="pos-auth-wordmark">CamelotBooks</div>
                    <div class="pos-auth-tagline">Enterprise Accounting & Advisory Services</div>
                </div>
            </div>
            <h1>Reset POS access</h1>
            <p class="pos-auth-lede">We'll send a 6-digit verification code to your registered email address.</p>
            <div class="pos-auth-bullets">
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">🔒</span> Secure code-based verification</div>
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">⏱</span> Codes expire after 10 minutes</div>
                <div class="pos-auth-bullet"><span class="pos-auth-bullet-icon">🔑</span> Reset your password or PIN</div>
            </div>
            <p class="pos-auth-footer">www.camelotbooks.com · +265 1 234 567</p>
        </aside>

        <main class="pos-auth-main">
            <div class="pos-auth-card">
                <h2 class="pos-auth-title">Reset access</h2>
                <p class="pos-auth-subtitle">Enter your email to receive a verification code.</p>

                @if ($errors->any())
                    <div class="pos-auth-error">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="pos-auth-success">{{ session('status') }}</div>
                @endif

                {{-- Step 1: Email entry --}}
                <div x-show="step === 'email'" x-transition>
                    <form method="POST" action="{{ route('pos.reset.send-code') }}">
                        @csrf
                        <div class="pos-auth-field">
                            <label class="pos-auth-label">Email address</label>
                            <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required autofocus
                                class="pos-auth-input" placeholder="e.g. elvis@camelotbooks.com">
                        </div>
                        <button type="submit" class="pos-auth-submit" :disabled="sending">
                            <span x-show="!sending">Send verification code →</span>
                            <span x-show="sending">Sending…</span>
                        </button>
                    </form>
                </div>

                {{-- Step 2: Code verification --}}
                <div x-show="step === 'code'" x-transition>
                    <p class="pos-auth-subtitle">Enter the 6-digit code sent to <b x-text="maskedEmail"></b></p>
                    <form method="POST" action="{{ route('pos.reset.verify-code') }}" x-ref="codeForm">
                        @csrf
                        <input type="hidden" name="code" :value="codeDigits.join('')">
                        <div class="pos-auth-pin-dots pos-auth-pin-dots--6">
                            <template x-for="i in 6" :key="i">
                                <span class="pos-auth-pin-dot" :class="codeDigits.length >= i && 'filled'"></span>
                            </template>
                        </div>
                        <div class="pos-auth-keypad">
                            <template x-for="n in [1,2,3,4,5,6,7,8,9,'clear',0,'back']" :key="'c'+n">
                                <button type="button" class="pos-auth-key"
                                    :class="(n === 'clear' || n === 'back') ? 'pos-auth-key--fn' : ''"
                                    @click="codeKey(n)"
                                    x-text="n === 'clear' ? 'Clear' : n === 'back' ? '⌫' : n"></button>
                            </template>
                        </div>
                        <button type="button" class="pos-auth-submit" :disabled="codeDigits.length < 6"
                            @click="$refs.codeForm.submit()">Verify code</button>
                    </form>
                    <div class="pos-auth-links">
                        <a href="#" @click.prevent="step = 'email'">← Back to email</a>
                        <span class="pos-auth-sep">·</span>
                        <a href="#" @click.prevent="resendCode()" x-show="canResend">Resend code</a>
                        <span x-show="!canResend" class="pos-auth-cooldown">Resend in <span x-text="cooldownLabel"></span></span>
                    </div>
                </div>

                <div class="pos-auth-links" style="margin-top:1.5rem">
                    <a href="{{ route('pos.login') }}">← Back to sign in</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function posReset() {
            return {
                step: 'email',
                maskedEmail: '{{ $email ? e(maskEmail($email)) : "" }}',
                sending: false,
                codeDigits: [],
                canResend: true,
                cooldownLabel: '',
                cooldownTimer: null,

                codeKey(n) {
                    if (n === 'clear') { this.codeDigits = []; return; }
                    if (n === 'back') { this.codeDigits.pop(); return; }
                    if (this.codeDigits.length < 6) this.codeDigits.push(String(n));
                    if (this.codeDigits.length === 6) {
                        this.$nextTick(() => this.$refs.codeForm.submit());
                    }
                },

                resendCode() {
                    fetch('{{ route('pos.reset.send-code') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: new URLSearchParams({ email: '{{ $email ?? "" }}' }),
                    }).then(r => r.json()).then(d => {
                        if (d.status === 'sent') {
                            this.startCooldown(30);
                        }
                    });
                },

                startCooldown(seconds) {
                    this.canResend = false;
                    this.cooldownTimer = setInterval(() => {
                        seconds--;
                        this.cooldownLabel = seconds + 's';
                        if (seconds <= 0) {
                            clearInterval(this.cooldownTimer);
                            this.canResend = true;
                        }
                    }, 1000);
                    this.cooldownLabel = seconds + 's';
                },
            };
        }
        function maskEmail(email) {
            if (!email) return '';
            const [user, domain] = email.split('@');
            return user[0] + '***@' + domain;
        }
    </script>
</body>
</html>

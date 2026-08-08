document.addEventListener('alpine:init', () => {
    // Verify-code page: 6-box OTP entry with auto-advance / backspace
    // navigation / paste support, a live countdown tied to the server-issued
    // expiry, and fetch-based verify + resend against the code endpoints.
    Alpine.data('verifyCode', (initial) => ({
        code: Array(6).fill(''),
        expiresAt: initial.expiresAt,
        countdown: '',
        expired: false,
        submitting: false,
        resending: false,
        resendDisabledUntil: 0,
        resendLabel: '',
        error: '',
        timer: null,
        resendTimer: null,

        init() {
            this.countdown = this.remainingLabel();
            this.resendLabel = this.resendRemainingLabel();
            this.timer = setInterval(() => {
                this.countdown = this.remainingLabel();
                this.resendLabel = this.resendRemainingLabel();
                if (this.expired) {
                    clearInterval(this.timer);
                }
            }, 1000);

            // Leaving via "Back to sign in" cancels the in-progress
            // verification server-side so the stored email goes inert.
            document.addEventListener('click', (e) => {
                const back = e.target.closest('.auth-login-back-link');
                if (!back) {
                    return;
                }
                fetch(initial.cancelUrl, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).catch(() => {});
            });
        },

        get remainingMs() {
            const diff = new Date(this.expiresAt).getTime() - Date.now();
            return diff > 0 ? diff : 0;
        },

        get complete() {
            return this.code.every((digit) => digit !== '');
        },

        get codeString() {
            return this.code.join('');
        },

        get resendDisabled() {
            return this.resendDisabledUntil > Date.now();
        },

        resendRemainingLabel() {
            if (!this.resendDisabled) {
                return '';
            }
            const total = Math.max(0, Math.floor((this.resendDisabledUntil - Date.now()) / 1000));
            const minutes = String(Math.floor(total / 60)).padStart(2, '0');
            const seconds = String(total % 60).padStart(2, '0');
            return `${minutes}:${seconds}`;
        },

        maybeAutoSubmit() {
            if (this.complete && !this.submitting && !this.expired) {
                this.submit();
            }
        },

        remainingLabel() {
            const total = Math.floor(this.remainingMs / 1000);

            if (total <= 0) {
                if (!this.expired) {
                    this.expired = true;
                }
                return '00:00';
            }

            const minutes = String(Math.floor(total / 60)).padStart(2, '0');
            const seconds = String(total % 60).padStart(2, '0');
            return `${minutes}:${seconds}`;
        },

        focusBox(index) {
            const el = this.$refs['box' + index];
            if (!el) {
                return;
            }
            el.focus();
            el.select();
        },

        onInput(index, event) {
            const digits = (event.target.value || '').replace(/\D/g, '');
            this.code[index] = digits.slice(-1);
            event.target.value = this.code[index];

            if (this.code[index] && index < 5) {
                this.focusBox(index + 1);
            }

            this.maybeAutoSubmit();
        },

        onKeydown(index, event) {
            if (event.key === 'Backspace' || event.key === 'Delete') {
                if (event.key === 'Delete' && this.code[index]) {
                    return;
                }
                if (!this.code[index] && index > 0) {
                    event.preventDefault();
                    this.focusBox(index - 1);
                }
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                this.focusBox(index - 1);
            } else if (event.key === 'ArrowRight' && index < 5) {
                event.preventDefault();
                this.focusBox(index + 1);
            }
        },

        onPaste(index, event) {
            event.preventDefault();

            const text = (event.clipboardData || window.clipboardData).getData('text') || '';
            const digits = text.replace(/\D/g, '');

            if (!digits) {
                return;
            }

            let cursor = index;
            for (const digit of digits) {
                if (cursor >= 6) {
                    break;
                }
                this.code[cursor] = digit;
                cursor += 1;
            }

            this.$nextTick(() => {
                this.focusBox(Math.min(cursor - 1, 5));
                this.maybeAutoSubmit();
            });
        },

        async submit() {
            if (!this.complete || this.submitting || this.expired) {
                return;
            }

            this.submitting = true;
            this.error = '';

            try {
                const response = await fetch(initial.verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ code: this.codeString }),
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    window.location.href = data.redirect;
                    return;
                }

                this.error = data.errors?.code?.[0]
                    || data.message
                    || 'That code isn\'t valid.';

                this.code = Array(6).fill('');
                this.$nextTick(() => this.focusBox(0));
            } catch (e) {
                this.error = 'Something went wrong. Please try again.';
            } finally {
                this.submitting = false;
            }
        },

        async resend() {
            if (this.resending || this.resendDisabled) {
                return;
            }

            this.resending = true;
            this.error = '';

            try {
                const response = await fetch(initial.resendUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    this.expiresAt = data.expires_at;
                    this.expired = false;
                    this.countdown = this.remainingLabel();
                    this.code = Array(6).fill('');
                    this.cooldown(data.resend_after ?? 30);
                    this.$nextTick(() => this.focusBox(0));
                    return;
                }

                if (response.status === 429 && data.retry_after) {
                    this.cooldown(data.retry_after);
                    this.error = data.message || 'Please wait a moment before requesting another code.';
                    return;
                }

                this.error = data.message || 'Something went wrong. Please try again.';
            } catch (e) {
                this.error = 'Something went wrong. Please try again.';
            } finally {
                this.resending = false;
            }
        },

        cooldown(seconds) {
            this.resendDisabledUntil = Date.now() + seconds * 1000;
            this.resendLabel = this.resendRemainingLabel();
            clearTimeout(this.resendTimer);
            this.resendTimer = setTimeout(() => {
                this.resendDisabledUntil = 0;
                this.resendLabel = this.resendRemainingLabel();
            }, seconds * 1000);
        },
    }));
});

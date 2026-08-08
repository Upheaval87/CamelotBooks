document.addEventListener('alpine:init', () => {
    // Forgot-password form: client-side email validation, in-place submission
    // to the existing password.email endpoint, and navigation to the
    // verify-code page when the email matches an account. Unknown emails get
    // an inline error returned by the server (422).
    Alpine.data('forgotPassword', () => ({
        email: '',
        submitting: false,
        error: '',

        get valid() {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email);
        },

        async submit() {
            if (!this.valid || this.submitting) {
                return;
            }

            this.submitting = true;
            this.error = '';

            try {
                const form = this.$refs.form;
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                if (response.ok) {
                    // Known email: the server issues the code and returns the
                    // verify-code URL for the client to follow.
                    const data = await response.json().catch(() => ({}));
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                    return;
                }

                let message = 'Something went wrong. Please try again.';
                try {
                    const data = await response.json();
                    if (data.errors?.email?.length) {
                        message = data.errors.email[0];
                    }
                } catch (e) {
                    // Keep the fallback message when the payload is not JSON.
                }

                this.error = message;
            } catch (e) {
                this.error = 'Something went wrong. Please try again.';
            } finally {
                this.submitting = false;
            }
        },
    }));
});

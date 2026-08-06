document.addEventListener('alpine:init', () => {
    // Forgot-password form: client-side email validation, in-place submission
    // to the existing password.email endpoint, and an enumeration-safe neutral
    // confirmation that replaces the form without a page navigation.
    Alpine.data('forgotPassword', () => ({
        email: '',
        submitting: false,
        sent: false,
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
                    this.sent = true;
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

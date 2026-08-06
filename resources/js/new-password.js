document.addEventListener('alpine:init', () => {
    // Set-new-password fields shared by the auth reset page and the profile
    // change-password card. The requirements checklist and its validation are
    // derived from the SAME policy the server enforces (PasswordPolicy), so
    // the UI can never drift from the backend.
    Alpine.data('newPassword', ({ policy, prefix = '' }) => ({
        policy,
        password: '',
        confirmation: '',
        showPassword: false,
        showConfirmation: false,
        error: '',

        met(item) {
            const value = this.password;
            switch (item.key) {
                case 'length':
                    return value.length >= (Number(item.min) || 8);
                case 'uppercase':
                    return /[A-Z]/.test(value);
                case 'lowercase':
                    return /[a-z]/.test(value);
                case 'number':
                    return /[0-9]/.test(value);
                case 'symbol':
                    return /[^A-Za-z0-9]/.test(value);
                default:
                    return true;
            }
        },

        get allMet() {
            return this.policy.every((item) => this.met(item));
        },

        get match() {
            return this.confirmation.length > 0 && this.password === this.confirmation;
        },

        handleSubmit() {
            if (!this.allMet) {
                this.error = 'Your password does not meet all requirements yet.';
                return false;
            }

            if (!this.match) {
                this.error = 'Your passwords do not match.';
                return false;
            }

            this.error = '';
            return true;
        },
    }));
});

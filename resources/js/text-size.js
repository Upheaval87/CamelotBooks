document.addEventListener('alpine:init', () => {
    Alpine.data('textSizeControl', (options = {}) => ({
        current: options.current || 'md',
        sizes: options.sizes || { sm: 0.9, md: 1.0, lg: 1.15 },

        init() {
            this.apply();
        },

        apply() {
            const scale = this.sizes[this.current] ?? 1;
            document.documentElement.style.setProperty('--text-scale', String(scale));
        },

        set(size) {
            if (this.current === size) return;
            const prev = this.current;
            this.current = size;
            this.apply();

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(window.textSizeUrl || '/preferences/text-size', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ size }),
            }).then(async (res) => {
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || ('HTTP ' + res.status));
                }
            }).catch((err) => {
                this.current = prev;
                this.apply();
                if (window.CB && window.CB.toast) {
                    window.CB.toast('error', 'Could not save text size', err.message);
                } else if (window.feedback) {
                    window.feedback.toast('error', 'Could not save text size: ' + err.message);
                }
            });
        },
    }));
});

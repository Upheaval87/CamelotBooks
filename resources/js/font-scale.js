document.addEventListener('alpine:init', () => {
    Alpine.data('fontScaleControl', (options = {}) => ({
        steps: Array.isArray(options.steps) && options.steps.length
            ? options.steps
            : [0.85, 1.00, 1.15, 1.30, 1.50],
        labels: Array.isArray(options.labels) && options.labels.length
            ? options.labels
            : ['Small', 'Normal', 'Large', 'Larger', 'Largest'],
        current: options.current ?? 1,

        get stepIndex() {
            const s = Number(this.current);
            const idx = this.steps.findIndex((v) => Math.abs(v - s) < 0.0001);
            if (idx !== -1) return idx;
            return this.steps.findIndex((v) => v === 1);
        },
        get label() {
            return this.labels[this.stepIndex] || 'Normal';
        },
        get atMin() {
            return this.stepIndex <= 0;
        },
        get atMax() {
            return this.stepIndex >= this.steps.length - 1;
        },

        init() {
            this.apply();
        },

        apply() {
            document.documentElement.style.setProperty('--font-scale', String(this.steps[this.stepIndex]));
        },

        setStep(delta) {
            const next = this.stepIndex + delta;
            if (next < 0 || next >= this.steps.length) return;

            const prev = this.stepIndex;
            this.current = this.steps[next];
            this.apply();

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(window.fontScaleUrl || '/preferences/font-scale', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ font_scale: this.steps[next] }),
            }).then(async (res) => {
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || ('HTTP ' + res.status));
                }
            }).catch((err) => {
                this.current = this.steps[prev];
                this.apply();
                if (window.CB && window.CB.toast) {
                    window.CB.toast('error', 'Could not save font size', err.message);
                } else if (window.feedback) {
                    window.feedback.toast('error', 'Could not save font size: ' + err.message);
                }
            });
        },
    }));
});

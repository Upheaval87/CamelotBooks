import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                // Standalone journal voucher print page. Its own entry so the
                // sheet never inherits app.css's unrelated @media print rules.
                'resources/css/journal-voucher.css',
                'resources/js/app.js',
                'resources/js/scoped-search-field.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
    },
});

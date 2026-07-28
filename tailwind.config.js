import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                'atlas': {
                    'navy': '#041C44',
                    'amber': '#F4B443',
                    'blue': '#6591E0',
                    'canvas': '#F2F2F2',
                    'danger': '#C1443B',
                },
                'navy': {
                    DEFAULT: '#041C44',
                    50: 'rgba(4,28,68,0.04)',
                    100: 'rgba(4,28,68,0.07)',
                    200: 'rgba(4,28,68,0.12)',
                    400: 'rgba(4,28,68,0.40)',
                    500: 'rgba(4,28,68,0.55)',
                    600: 'rgba(4,28,68,0.68)',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'heading': ['24px', { lineHeight: '1.3', fontWeight: '700' }],
                'card-title': ['13px', { lineHeight: '1.4', fontWeight: '500' }],
                'body': ['13px', { lineHeight: '1.5', fontWeight: '400' }],
                'meta': ['11px', { lineHeight: '1.4', fontWeight: '400' }],
            },
            borderRadius: {
                'card': '10px',
            },
            transitionProperty: {
                'sidebar': 'background-color, color, transform, box-shadow',
            },
        },
    },

    plugins: [forms],
};

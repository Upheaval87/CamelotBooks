import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    DEFAULT: 'rgba(28,37,48,<alpha-value>)',
                    soft: 'rgba(91,101,114,<alpha-value>)',
                    faint: 'rgba(140,147,160,<alpha-value>)',
                },
                line: {
                    DEFAULT: 'rgba(229,227,220,<alpha-value>)',
                },
                panel: {
                    DEFAULT: 'rgba(246,245,241,<alpha-value>)',
                },
                gold: {
                    DEFAULT: 'rgba(156,122,60,<alpha-value>)',
                    soft: 'rgba(243,236,220,<alpha-value>)',
                    line: 'rgba(217,200,154,<alpha-value>)',
                },
                brick: {
                    DEFAULT: 'rgba(142,59,59,<alpha-value>)',
                    soft: 'rgba(245,233,231,<alpha-value>)',
                },
                forest: {
                    DEFAULT: 'rgba(62,107,78,<alpha-value>)',
                    soft: 'rgba(233,241,236,<alpha-value>)',
                },
                atlas: {
                    navy: 'rgba(28,37,48,<alpha-value>)',
                    amber: 'rgba(156,122,60,<alpha-value>)',
                    danger: 'rgba(142,59,59,<alpha-value>)',
                    blue: 'rgba(59,130,246,<alpha-value>)',
                },
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                neutral: {
                    0: '#ffffff',
                    50: '#f8f9fc',
                    100: '#f1f3f8',
                    150: '#e9edf3',
                    200: '#dce1ea',
                    300: '#bcc3d1',
                    400: '#959ead',
                    500: '#6b7484',
                    600: '#4f5767',
                    700: '#394051',
                    800: '#262d3d',
                    900: '#161b2b',
                    950: '#0d1225',
                },
                surface: {
                    DEFAULT: '#ffffff',
                    secondary: '#f8f9fc',
                    tertiary: '#f1f3f8',
                    ...{}
                },
                accent: {
                    DEFAULT: '#6366f1',
                    hover: '#4f46e5',
                    light: '#eef2ff',
                    subtle: 'rgba(99,102,241,0.08)',
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                success: {
                    DEFAULT: '#10b981',
                    light: '#ecfdf5',
                    dark: '#065f46',
                },
                warning: {
                    DEFAULT: '#f59e0b',
                    light: '#fffbeb',
                    dark: '#92400e',
                },
                danger: {
                    DEFAULT: '#ef4444',
                    light: '#fef2f2',
                    dark: '#991b1b',
                },
                info: {
                    DEFAULT: '#3b82f6',
                    light: '#eff6ff',
                    dark: '#1e40af',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'xs': ['0.75rem', { lineHeight: '1rem' }],
                'sm': ['0.8125rem', { lineHeight: '1.25rem' }],
                'base': ['0.875rem', { lineHeight: '1.375rem' }],
                'lg': ['1rem', { lineHeight: '1.5rem' }],
                'xl': ['1.125rem', { lineHeight: '1.625rem' }],
                '2xl': ['1.25rem', { lineHeight: '1.75rem' }],
                '3xl': ['1.5rem', { lineHeight: '1.875rem' }],
                'heading': ['1.5rem', { lineHeight: '1.2', fontWeight: '600' }],
                'subheading': ['1.125rem', { lineHeight: '1.4', fontWeight: '500' }],
                'card-title': ['0.8125rem', { lineHeight: '1.4', fontWeight: '500' }],
                'meta': ['0.6875rem', { lineHeight: '1.25', fontWeight: '400' }],
            },
            borderRadius: {
                'sm': '4px',
                DEFAULT: '6px',
                'md': '8px',
                'lg': '12px',
                'xl': '16px',
                '2xl': '20px',
                'card': '12px',
                'pill': '9999px',
            },
            boxShadow: {
                'soft': '0 1px 2px 0 rgba(0,0,0,0.03), 0 1px 3px 0 rgba(0,0,0,0.04)',
                'card': '0 1px 2px 0 rgba(0,0,0,0.02), 0 1px 6px -1px rgba(0,0,0,0.04), 0 2px 4px -1px rgba(0,0,0,0.02)',
                'elevated': '0 4px 6px -1px rgba(0,0,0,0.04), 0 2px 4px -1px rgba(0,0,0,0.02), 0 10px 15px -3px rgba(0,0,0,0.04)',
                'dropdown': '0 4px 6px -1px rgba(0,0,0,0.03), 0 10px 24px -4px rgba(0,0,0,0.08), 0 1px 3px 0 rgba(0,0,0,0.02)',
                'modal': '0 8px 32px -4px rgba(0,0,0,0.12), 0 2px 8px -2px rgba(0,0,0,0.04)',
                'sidebar': '2px 0 12px -4px rgba(0,0,0,0.08)',
                'nav': '0 1px 3px 0 rgba(0,0,0,0.02), 0 1px 2px 0 rgba(0,0,0,0.03)',
                'btn': '0 1px 2px 0 rgba(0,0,0,0.03)',
                'btn-hover': '0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px 0 rgba(0,0,0,0.04)',
                'inset': 'inset 0 2px 4px 0 rgba(0,0,0,0.02)',
            },
            transitionDuration: {
                '250': '250ms',
                '350': '350ms',
                '400': '400ms',
            },
            transitionTimingFunction: {
                'bounce-in': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
                'smooth': 'cubic-bezier(0.22, 1, 0.36, 1)',
                'spring': 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
            },
            animation: {
                'fade-in': 'fadeIn 0.2s ease-out',
                'fade-in-up': 'fadeInUp 0.25s ease-out',
                'fade-in-down': 'fadeInDown 0.25s ease-out',
                'fade-in-scale': 'fadeInScale 0.2s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'slide-in-left': 'slideInLeft 0.3s ease-out',
                'scale-in': 'scaleIn 0.15s ease-out',
                'spin-slow': 'spin 2s linear infinite',
                'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                'shimmer': 'shimmer 2s infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeInDown: {
                    '0%': { opacity: '0', transform: 'translateY(-8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeInScale: {
                    '0%': { opacity: '0', transform: 'scale(0.96)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(16px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                slideInLeft: {
                    '0%': { opacity: '0', transform: 'translateX(-16px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },
            spacing: {
                '4.5': '1.125rem',
                '18': '4.5rem',
                '88': '22rem',
                '120': '30rem',
            },
            maxWidth: {
                '8xl': '88rem',
                '9xl': '96rem',
            },
        },
    },

    plugins: [forms],
};

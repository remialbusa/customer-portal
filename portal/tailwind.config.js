import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // DaisyUI generates component classes (btn, card, alert, …) on demand
    // when their strings appear in templates; tailwind's content scan picks
    // them up automatically. This safelist catches any we generate at
    // runtime via {{ $variant }} interpolation in Blade components.
    safelist: [
        'btn-primary', 'btn-secondary', 'btn-accent', 'btn-info',
        'btn-success', 'btn-warning', 'btn-error', 'btn-ghost',
        'btn-outline', 'btn-circle', 'btn-square', 'btn-block',
        'badge-primary', 'badge-secondary', 'badge-accent', 'badge-info',
        'badge-success', 'badge-warning', 'badge-error',
        'alert-info', 'alert-success', 'alert-warning', 'alert-error',
        'progress-primary', 'progress-secondary', 'progress-accent',
        'progress-info', 'progress-success', 'progress-warning', 'progress-error',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    navy:       '#0A2540',
                    'navy-2':   '#0E2F50',
                    blue:       '#1E5BD6',
                    'blue-2':   '#3FA7E0',
                    'blue-3':   '#0D47A1',
                    green:      '#2E9B3F',
                    'green-2':  '#43A047',
                    'green-3':  '#66BB6A',
                    cream:      '#F7FAFC',
                    slate:      '#475569',
                    mist:       '#E2E8F0',
                },
            },
            boxShadow: {
                soft:  '0 4px 24px -8px rgba(10, 37, 64, 0.12)',
                glow:  '0 12px 40px -12px rgba(30, 91, 214, 0.45)',
                card:  '0 14px 34px 0 rgba(10, 37, 64, 0.08)',
            },
            backgroundImage: {
                'brand-gradient': 'linear-gradient(135deg, #2E9B3F 0%, #7FC8A9 50%, #3FA7E0 100%)',
                'brand-cta':      'linear-gradient(135deg, #1E5BD6 0%, #3FA7E0 100%)',
                'brand-cta-2':    'linear-gradient(135deg, #2E9B3F 0%, #66BB6A 100%)',
                'brand-radial':   'radial-gradient(ellipse at center, #E8F4F8 0%, rgba(232,244,248,0) 70%)',
            },
        },
    },

    plugins: [forms, daisyui],

    // DaisyUI custom theme: "mcbio" — derived from the logo colors
    // (#1E3A8A navy wordmark, #2E9B3F medical green). Tone is
    // "calm clinical + modern SaaS + friendly concierge".
    daisyui: {
        themes: [
            {
                mcbio: {
                    'primary':         '#0A2540',  // brand-navy  — strong, anchored
                    'primary-content': '#FFFFFF',
                    'secondary':       '#2E9B3F',  // brand-green — medical, calm
                    'secondary-content': '#FFFFFF',
                    'accent':          '#3FA7E0',  // brand-blue-2 — sky highlight
                    'accent-content':  '#FFFFFF',
                    'neutral':         '#475569',  // brand-slate
                    'neutral-content': '#F7FAFC',
                    'base-100':        '#FFFFFF',  // card surface
                    'base-200':        '#F7FAFC',  // brand-cream — page bg
                    'base-300':        '#E2E8F0',  // brand-mist — borders
                    'base-content':    '#0F172A',  // body text
                    'info':            '#3FA7E0',
                    'info-content':    '#FFFFFF',
                    'success':         '#2E9B3F',
                    'success-content': '#FFFFFF',
                    'warning':         '#F59E0B',
                    'warning-content': '#1F2937',
                    'error':           '#DC2626',
                    'error-content':   '#FFFFFF',
                    '--rounded-box':   '0.75rem',  // soft, friendly corners
                    '--rounded-btn':   '0.5rem',
                    '--rounded-badge': '1rem',
                    '--animation-btn': '0.2s',
                    '--btn-text-case': 'none',     // not all-caps
                    '--border-btn':    '1px',
                },
            },
        ],
        base: true,        // apply base resets (recommended)
        styled: true,      // ship styled components
        utils: true,       // ship utility helpers (e.g. glass, no-animation)
        logs: false,       // keep build output clean
    },
};

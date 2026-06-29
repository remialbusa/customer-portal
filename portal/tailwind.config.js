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

    plugins: [forms],
};

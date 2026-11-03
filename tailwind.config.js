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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Admin palette: one plum family plus three status pastels. Same values live as --vd-*
            // variables in resources/css/admin/motion.css (plain CSS, Chart.js). Do not add colours
            // without updating docs/design/admin-design-system.md.
            colors: {
                vd: {
                    ink: '#2B1730', muted: '#6B5D70', paper: '#FBF7F2', tint: '#F7F1F7',
                    lavender: '#E4DAEA', 'plum-soft': '#CDB9D4', plum: '#3B1735', 'plum-hover': '#4D1F45',
                    line: '#ECE4EC', 'line-strong': '#DDD0E0',
                    'success-bg': '#E8F4EA', 'success-line': '#BFE0C6', 'success-ink': '#1F6B35',
                    'warning-bg': '#FBF1D6', 'warning-line': '#EBD79B', 'warning-ink': '#7A5A00',
                    'danger-bg': '#FBE6E6', 'danger-line': '#EFC0C0', 'danger-ink': '#A32B2B',
                    // Accent: the four Dashboard stat cards only (tint + matching solid for the icon chip).
                    'peach': '#F6DCCF', 'peach-line': '#E8C2B0', clay: '#C05E41',
                    'sand': '#F3E9C8', 'sand-line': '#E4D6A6', gold: '#B8932A',
                    'mauve': '#E9D8EC', 'mauve-line': '#D4BCD9', orchid: '#805487',
                },
            },
        },
    },

    plugins: [forms],
};

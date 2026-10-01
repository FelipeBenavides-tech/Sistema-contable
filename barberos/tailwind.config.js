import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#EEF3FF',
                    100: '#DCE6FF',
                    200: '#B9CCFF',
                    500: '#0047FF',
                    600: '#003BD6',
                    700: '#002FA8',
                    900: '#0A1628',
                },
                accent: {
                    100: '#FFF4D6',
                    400: '#FFC633',
                    500: '#FFB800',
                },
                ink:   '#0A1628',
                muted: '#5A6A8A',
                line:  '#DDE3FF',
                fondo: '#F0F4FF',
            },
            boxShadow: {
                card: '0 1px 2px rgba(10, 22, 40, 0.04), 0 1px 12px rgba(10, 22, 40, 0.04)',
            },
        },
    },

    plugins: [forms],
};

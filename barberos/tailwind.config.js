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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Acento principal: azul marino apagado
                brand: {
                    50:  '#F3F5F9',
                    100: '#E4E9F2',
                    200: '#C9D3E5',
                    300: '#A1B1CF',
                    400: '#6F86B2',
                    500: '#4A6396',
                    600: '#3B527F',
                    700: '#2F4267',
                    800: '#253453',
                    900: '#18233A',
                },
                // Dorado suave: solo para la marca (logo)
                accent: {
                    100: '#F5EEDC',
                    400: '#D2B56E',
                    500: '#C2A15A',
                },
                ink:   '#0F172A',
                muted: '#64748B',
                line:  '#E5E7EB',
                fondo: '#F6F7F9',
            },
            boxShadow: {
                card: '0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06)',
                elevada: '0 4px 6px -2px rgba(15, 23, 42, 0.05), 0 12px 24px -6px rgba(15, 23, 42, 0.12)',
            },
        },
    },

    plugins: [forms],
};

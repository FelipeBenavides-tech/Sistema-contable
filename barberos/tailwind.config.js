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
                // Los colores de marca salen de variables CSS (resources/css/app.css)
                // para que el salón de belleza use su paleta pastel sin duplicar pantallas.
                // Clásico (barbería): azul marino apagado. Salón: rosa empolvado.
                brand: Object.fromEntries(
                    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]
                        .map((tono) => [tono, `rgb(var(--brand-${tono}) / <alpha-value>)`]),
                ),
                // Solo para la marca (logo). Clásico: dorado suave. Salón: lavanda.
                accent: {
                    100: 'rgb(var(--accent-100) / <alpha-value>)',
                    400: 'rgb(var(--accent-400) / <alpha-value>)',
                    500: 'rgb(var(--accent-500) / <alpha-value>)',
                },
                ink:   '#0F172A',
                muted: '#64748B',
                line:  '#E5E7EB',
                fondo: 'rgb(var(--fondo) / <alpha-value>)',
            },
            boxShadow: {
                card: '0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06)',
                elevada: '0 4px 6px -2px rgba(15, 23, 42, 0.05), 0 12px 24px -6px rgba(15, 23, 42, 0.12)',
            },
        },
    },

    plugins: [forms],
};

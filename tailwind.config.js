import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                // English fonts - Elegant/Premium
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
                serif: ['Cormorant Garamond', ...defaultTheme.fontFamily.serif],
                display: ['Cormorant Garamond', ...defaultTheme.fontFamily.serif],
                // Arabic fonts (for explicit use)
                'arabic': ['IBM Plex Sans Arabic', 'Segoe UI', 'Tahoma', 'sans-serif'],
                'arabic-serif': ['Noto Naskh Arabic', 'Traditional Arabic', 'serif'],
            },
            colors: {
                'red-banana': {
                    50: '#fff5f5',
                    100: '#fed7d7',
                    200: '#feb2b2',
                    300: '#fc8181',
                    400: '#f56565',
                    500: '#e53e3e',
                    600: '#c53030',
                    700: '#9b2c2c',
                    800: '#822727',
                    900: '#63171b',
                },
                'banana-yellow': {
                    50: '#fefce8',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
                'warm-orange': {
                    50: '#fff7ed',
                    100: '#ffedd5',
                    200: '#fed7aa',
                    300: '#fdba74',
                    400: '#fb923c',
                    500: '#f97316',
                    600: '#ea580c',
                    700: '#c2410c',
                    800: '#9a3412',
                    900: '#7c2d12',
                },
                primary: {
                    50: '#fff5f5',
                    100: '#fed7d7',
                    200: '#feb2b2',
                    300: '#fc8181',
                    400: '#f56565',
                    500: '#e53e3e',
                    600: '#c53030',
                    700: '#9b2c2c',
                    800: '#822727',
                    900: '#63171b',
                },
                secondary: {
                    50: '#fefce8',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
            },
        },
    },
    plugins: [],
};

import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                // English fonts - Clean & Modern
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                // Arabic fonts (for explicit use)
                'arabic': ['IBM Plex Sans Arabic', 'Segoe UI', 'Tahoma', 'sans-serif'],
                'arabic-serif': ['Noto Naskh Arabic', 'Traditional Arabic', 'serif'],
            },
            colors: {
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

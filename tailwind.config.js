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
                sans: ['Geist', 'Geist Sans', ...defaultTheme.fontFamily.sans],
                mono: ['Geist Mono', ...defaultTheme.fontFamily.mono],
                serif: ['Geist', ...defaultTheme.fontFamily.sans],
                display: ['Geist', ...defaultTheme.fontFamily.sans],
                // Arabic fonts (for explicit use)
                'arabic': ['IBM Plex Sans Arabic', 'Segoe UI', 'Tahoma', 'sans-serif'],
                'arabic-serif': ['IBM Plex Sans Arabic', 'Segoe UI', 'Tahoma', 'sans-serif'],
            },
            colors: {
                // Warm monochrome: gray-family utilities all render warm (skill: one palette, no cool grays)
                gray: {
                    50: '#F7F6F3', 100: '#F1F1EF', 200: '#EAEAEA', 300: '#D4D4D2',
                    400: '#A8A8A4', 500: '#787774', 600: '#555552', 700: '#3F3F3C',
                    800: '#2A2A28', 900: '#111111', 950: '#0C0C0D',
                },
                zinc: {
                    50: '#F7F6F3', 100: '#F1F1EF', 200: '#EAEAEA', 300: '#D4D4D2',
                    400: '#A8A8A4', 500: '#787774', 600: '#555552', 700: '#3F3F3C',
                    800: '#2A2A28', 900: '#111111', 950: '#0C0C0D',
                },
                slate: {
                    50: '#F7F6F3', 100: '#F1F1EF', 200: '#EAEAEA', 300: '#D4D4D2',
                    400: '#A8A8A4', 500: '#787774', 600: '#555552', 700: '#3F3F3C',
                    800: '#2A2A28', 900: '#111111', 950: '#0C0C0D',
                },
                stone: {
                    50: '#F7F6F3', 100: '#F1F1EF', 200: '#EAEAEA', 300: '#D4D4D2',
                    400: '#A8A8A4', 500: '#787774', 600: '#555552', 700: '#3F3F3C',
                    800: '#2A2A28', 900: '#111111', 950: '#0C0C0D',
                },
                neutral: {
                    50: '#F7F6F3', 100: '#F1F1EF', 200: '#EAEAEA', 300: '#D4D4D2',
                    400: '#A8A8A4', 500: '#787774', 600: '#555552', 700: '#3F3F3C',
                    800: '#2A2A28', 900: '#111111', 950: '#0C0C0D',
                },
                // Single brand accent (Taste Skill: one accent, locked)
                brand: {
                    50: '#FEF2F2',
                    100: '#FEE2E2',
                    200: '#FECACA',
                    300: '#FCA5A5',
                    400: '#F87171',
                    500: '#EF4444',
                    600: '#DC2626',
                    700: '#B91C1C',
                    800: '#991B1B',
                    900: '#7F1D1D',
                },
                primary: {
                    50: '#FEF2F2',
                    100: '#FEE2E2',
                    200: '#FECACA',
                    300: '#FCA5A5',
                    400: '#F87171',
                    500: '#EF4444',
                    600: '#DC2626',
                    700: '#B91C1C',
                    800: '#991B1B',
                    900: '#7F1D1D',
                },
                secondary: {
                    50: '#FBFBFA',
                    100: '#F7F6F3',
                    200: '#F1F1EF',
                    300: '#EAEAEA',
                    400: '#D4D4D2',
                    500: '#A8A8A4',
                    600: '#787774',
                    700: '#555552',
                    800: '#333331',
                    900: '#111111',
                },
                // Remap AI-default palettes to the single brand accent
                blue: {
                    50: '#FEF2F2',
                    100: '#FEE2E2',
                    200: '#FECACA',
                    300: '#FCA5A5',
                    400: '#F87171',
                    500: '#EF4444',
                    600: '#DC2626',
                    700: '#B91C1C',
                    800: '#991B1B',
                    900: '#7F1D1D',
                },
                indigo: {
                    50: '#FEF2F2',
                    100: '#FEE2E2',
                    200: '#FECACA',
                    300: '#FCA5A5',
                    400: '#F87171',
                    500: '#EF4444',
                    600: '#DC2626',
                    700: '#B91C1C',
                    800: '#991B1B',
                    900: '#7F1D1D',
                },
                violet: {
                    50: '#FEF2F2',
                    100: '#FEE2E2',
                    200: '#FECACA',
                    300: '#FCA5A5',
                    400: '#F87171',
                    500: '#EF4444',
                    600: '#DC2626',
                    700: '#B91C1C',
                    800: '#991B1B',
                    900: '#7F1D1D',
                },
            },
            borderRadius: {
                // Taste Skill shape lock: crisp 6-14px scale everywhere
                none: '0',
                sm: '4px',
                DEFAULT: '6px',
                md: '8px',
                lg: '10px',
                xl: '12px',
                '2xl': '12px',
                '3xl': '14px',
                full: '9999px',
            },
            boxShadow: {
                // Taste Skill minimalist: practically non-existent, ultra-diffuse
                sm: '0 1px 2px 0 rgb(0 0 0 / 0.04)',
                DEFAULT: '0 1px 3px 0 rgb(0 0 0 / 0.05)',
                md: '0 4px 16px -2px rgb(0 0 0 / 0.05)',
                lg: '0 6px 20px -4px rgb(0 0 0 / 0.06)',
                xl: '0 8px 24px -6px rgb(0 0 0 / 0.06)',
                '2xl': '0 12px 32px -8px rgb(0 0 0 / 0.07)',
                none: 'none',
            },
        },
    },
    plugins: [],
};

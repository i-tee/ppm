/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/js/**/*.{vue,js,ts}', // Vue-компоненты, включая папку dashboard
    './public/index.html', // HTML-шаблон
    './resources/views/**/*.blade.php', // Blade-шаблоны Laravel
  ],
  theme: {
    extend: {
      colors: {
        primary: 'var(--va-primary)', // Синхронизация с цветами Vuestic
        secondary: 'var(--va-secondary)',
        success: 'var(--va-success)',
        info: 'var(--va-info)',
        danger: 'var(--va-danger)',
        warning: 'var(--va-warning)',
      },
      fontFamily: {
        inter: ['Inter', 'sans-serif'], // Поддержка шрифта Inter в Tailwind
      },
    },
  },
  plugins: [],
};
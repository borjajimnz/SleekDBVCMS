/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './public/**/*.blade.php',
    './public/*.php',
    './src/**/*.php',
    './storage/stores/**/*.json',
    './storage/settings.json',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};

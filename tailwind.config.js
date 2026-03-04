/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: ['selector', '[data-theme="dark"]'],
    content: [
        './views/**/*.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#e67e22',
                secondary: '#2c3e50',
                success: '#2ecc71',
                warning: '#f39c12',
                danger: '#e74c3c',
            }
        }
    },
    plugins: [],
};

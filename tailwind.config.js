/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.php',
    './assets/js/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        'wp-primary': '#2271b1',
        'wp-primary-darker': '#135e96',
        'wp-admin-background': '#f0f0f1',
        'wp-border': '#c3c4c7'
      }
    }
  },
  plugins: [
    require('@tailwindcss/forms')
  ],
  // Prevent Tailwind from conflicting with WordPress admin styles
  important: '#wprb-admin',
  prefix: 'wprb-',
  corePlugins: {
    preflight: false
  }
}

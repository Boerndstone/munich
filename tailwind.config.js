const colors = require('tailwindcss/colors')

// Loaded via @config from assets/styles/app.css (Tailwind v4).
// darkMode + important live in app.css (@custom-variant, @import … important).
module.exports = {
  theme: {
    colors: {
      transparent: 'transparent',
      current: 'currentColor',
      black: colors.black,
      white: colors.white,
      gray: colors.slate,
      green: colors.green,
      indigo: colors.indigo,
      red: colors.red,
      amber: colors.amber,
      yellow: colors.amber,
      teal: colors.teal,
      sky: colors.sky,
    },
    backgroundColor: {
      transparent: 'transparent',
      current: 'currentColor',
      black: colors.black,
      white: colors.white,
      gray: colors.slate,
      green: colors.green,
      indigo: colors.indigo,
      red: colors.red,
      amber: colors.amber,
      yellow: colors.amber,
      teal: colors.teal,
      sky: colors.sky,
    },
    screens: {
      sm: '640px',
      md: '768px',
      lg: '1024px'
    },
    extend: {
      listStyleType: {
        square: 'square',
      },
    }
  },
}
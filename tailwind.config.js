const colors = require('tailwindcss/colors')

module.exports = {
  //prefix: 'munich-',
  important: true,
  corePlugins: {
  },
  //purge: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  content: [
    "./assets/**/*.{vue,js,ts,jsx,tsx}",
    "./templates/**/*.{html,twig}",
],
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
      yellow: colors.amber,
      teal: colors.teal,
      sky: colors.sky,
    },
    listStyleType: {
        square: 'square'
    },
    screens: {
      sm: '640px',
      md: '768px',
      lg: '1024px'
    },
    container: {
      center: true,
      padding: '.5rem',
    },
    extend: {
    }
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
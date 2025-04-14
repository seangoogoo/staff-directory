/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./public/**/*.php"],
  theme: {
    fontFamily: {
      sans: ['Outfit', 'sans-serif'], // Add Outfit as the primary sans-serif font
      remixicon: ['remixicon'],
    },
    fontWeight: {
      thin: '100',
      extralight: '200',
      light: '300',
      normal: '400',
      medium: '500',
      semibold: '600',
      bold: '700',
      extrabold: '800',
      black: '900',
    },
    extend: {
      screens: {
        'nav': '1300px', // Custom breakpoint for navigation text visibility
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}

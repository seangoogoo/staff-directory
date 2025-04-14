module.exports = {
  plugins: [
    require('./src/build-tools/postcss-tailwind-to-css-vars'),
    require('tailwindcss'),
    require('autoprefixer'),
  ]
};

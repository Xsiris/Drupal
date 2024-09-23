/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./templates/**/*.twig'],
  theme: {
    extend: {
      colors:{
        "omdDarkSlateGrey" : "hsl(234, 29%, 20%)",
        "omdCharcoalGrey" : "hsl(235, 18%, 26%)",
        "omgGrey" : "hsl(231, 7%, 60%)",
        "omdWhite" : "hsl(0, 0%, 100%)",
        "omdTomato" : "hsl(4, 100%, 67%)",
      }
    },
    fontFamily:{
      'Roboto' : ['Roboto']
    },
  },
  plugins: [],
}


const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,
  pages: {
    index: {
      entry: 'src/main.js', // main entry point
      title: 'MERITS-ATB', // website title
    },
  }
})

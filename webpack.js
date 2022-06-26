const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry.submit =  path.resolve(path.join('src', 'submit.js'))
webpackConfig.entry.settings = path.resolve(path.join('src', 'settings.js'))

module.exports = webpackConfig

// webpack.config.js
var Encore = require('@symfony/webpack-encore');

Encore
// the project directory where all compiled assets will be stored
    .setOutputPath('web/build/')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/build/')

    // will create web/build/app.js and web/build/app.css
    .addEntry('app', './assets/js/app.js')

    // other js modules
    .addEntry('list', './assets/js/list.js')
    .addEntry('person', './assets/js/person.js')
    .addEntry('wlt_agreement', './assets/js/wlt/agreement.js')
    .addEntry('wlt_learning_program', './assets/js/wlt/learning_program.js')
    .addEntry('wlt_learning_program_import', './assets/js/wlt/learning_program_import.js')

    // allow legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()

    // enable source maps during development
    .enableSourceMaps(!Encore.isProduction())

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // show OS notifications when builds finish/fail
    .enableBuildNotifications()

    // create hashed filenames (e.g. app.abc123.css)
    // .enableVersioning()

    // allow sass/scss files to be processed
    .enableSassLoader()

    // enable post css loader
    .enablePostCssLoader()

    .enableSingleRuntimeChunk()
;

// export the final configuration
module.exports = Encore.getWebpackConfig();

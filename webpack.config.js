// webpack.config.js
const Encore = require('@symfony/webpack-encore');
if (!Encore.isRuntimeEnvironmentConfigured()) {
        Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

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
    .addEntry('wlt_agreement_list', './assets/js/wlt/agreement_list.js')
    .addEntry('wlt_learning_program', './assets/js/wlt/learning_program.js')
    .addEntry('wlt_learning_program_import', './assets/js/wlt/learning_program_import.js')

    // CKEditor
    .copyFiles([
            {from: './node_modules/ckeditor4/', to: 'ckeditor/[path][name].[ext]', pattern: /\.(js|css)$/, includeSubdirectories: false},
            {from: './node_modules/ckeditor4/adapters', to: 'ckeditor/adapters/[path][name].[ext]'},
            {from: './node_modules/ckeditor4/lang', to: 'ckeditor/lang/[path][name].[ext]'},
            {from: './node_modules/ckeditor4/plugins', to: 'ckeditor/plugins/[path][name].[ext]'},
            {from: './node_modules/ckeditor4/skins', to: 'ckeditor/skins/[path][name].[ext]'}
    ])

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

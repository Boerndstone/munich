var Encore = require('@symfony/webpack-encore');
var path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('areas', './assets/js/areas.js')
    .addEntry('global', './assets/js/global.js')
    .addEntry('frontend', './assets/js/frontend.js')
    .addStyleEntry('tailwind', './assets/css/tailwind.css')
    .addEntry('admin', './assets/admin.js')
    // enable post css loader
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            // directory where the postcss.config.js file is stored
            config: './postcss.config.js'
        };
    })
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]'
    })

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    //.splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()
    .addAliases({
        '@': path.resolve(__dirname, 'assets', 'js'),
        styles: path.resolve(__dirname, 'assets', 'scss'),
    })
    //.disableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    //.cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .cleanupOutputBeforeBuild()
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabel((babelConfig) => {

    }, {
        useBuiltIns: 'usage',
        corejs: 2,
    })

    // enables Sass/SCSS support
    .enableSassLoader()
    .enableVueLoader()

    // gives better module CSS naming in dev
    /*.configureCssLoader((config) => {
        if (!Encore.isProduction() && config.modules) {
            config.modules.localIdentName = '[name]_[local]_[hash:base64:5]';
        }
    })*/

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    .enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()
;

/*if (!Encore.isProduction()) {
    Encore.disableCssExtraction();
}*/

module.exports = Encore.getWebpackConfig();
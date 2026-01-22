const Encore = require("@symfony/webpack-encore");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore
  // directory where compiled assets will be stored
  .setOutputPath("public/build/admin/")
  // public path used by the web server to access the output path
  .setPublicPath("/build/admin")

  /*
   * ENTRY CONFIG - Admin only
   */
  .addEntry("admin", "./assets/admin.js")

  // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
  .enableStimulusBridge("./assets/controllers.json")

  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()

  // will require an extra script tag for runtime.js
  .enableSingleRuntimeChunk()

  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  // enables hashed filenames (e.g. admin.abc123.css)
  .enableVersioning(Encore.isProduction())

  .configureBabel((babelConfig) => {
    babelConfig.plugins.push("@babel/plugin-proposal-class-properties");
  })

  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = "usage";
    config.corejs = "3.23";
  });

module.exports = Encore.getWebpackConfig();

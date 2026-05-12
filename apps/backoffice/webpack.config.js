const Encore = require("@symfony/webpack-encore");

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore
  .setOutputPath("public/build/")
  .setPublicPath("/build")
  .enableSassLoader()
  .enablePostCssLoader()
  .addEntry("admin", "./assets/admin.js")
  .addEntry("topo_editor", "./assets/topo_editor.js")
  .copyFiles({
    from: "./assets/images",
    to: "images/[path][name].[ext]",
  })
  .enableStimulusBridge("./assets/controllers.json")
  .splitEntryChunks()

    // enables the Symfony UX Stimulus bridge (used in assets/stimulus_bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .configureCssMinimizerPlugin((options) => {
    options.minimizerOptions = {
      preset: ["default", { discardComments: { removeAll: true } }],
    };
  })
  .configureBabel((babelConfig) => {
    babelConfig.plugins.push("@babel/plugin-proposal-class-properties");
  })
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = "usage";
    config.corejs = "3.23";
  });

module.exports = Encore.getWebpackConfig();

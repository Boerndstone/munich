const Encore = require("@symfony/webpack-encore");

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore
  .setOutputPath("public/build/")
  .setPublicPath("/build")
  .enableSassLoader()
  .enablePostCssLoader()
  .addEntry("app", "./assets/app.js")
  .addEntry("map", "./assets/map.js")
  .addEntry("gallery", "./assets/gallery.js")
  .addEntry("rock", "./assets/rock.js")
  .addEntry("routeparams", "./assets/routeparams.js")
  .addEntry("filter", "./assets/filter.js")
  .addEntry("topo_editor", "./assets/topo_editor.js")
  .copyFiles({
    from: "./assets/images",
    to: "images/[path][name].[ext]",
  })
  .enableStimulusBridge("./assets/controllers.json")
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .configureBabel((babelConfig) => {
    babelConfig.plugins.push("@babel/plugin-proposal-class-properties");
  })
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = "usage";
    config.corejs = "3.23";
  });

module.exports = Encore.getWebpackConfig();

const path = require("path");
const Encore = require("@symfony/webpack-encore");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore
  // directory where compiled assets will be stored
  .setOutputPath("public/build/")
  // public path used by the web server to access the output path
  .setPublicPath("/build")

  .enableSassLoader()
  .enablePostCssLoader()

  /*
   * ENTRY CONFIG - Frontend only
   */
  .addEntry("app", "./assets/app.js")
  
  // Page-specific entry points - only loaded when needed
  .addEntry("map", "./assets/map.js")
  .addEntry("gallery", "./assets/gallery.js")
  .addEntry("rock", "./assets/rock.js")
  .addEntry("routeparams", "./assets/routeparams.js")
  .addEntry("filter", "./assets/filter.js")
  // EasyAdmin / backend (was a separate Encore build; merged so one `npm run build` serves /admin)
  .addEntry("admin", "./assets/admin.js")

  .copyFiles({
    from: "./assets/images",
    to: "images/[path][name].[ext]",
  })

  // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
  .enableStimulusBridge("./assets/controllers.json")

  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()

  // will require an extra script tag for runtime.js
  .enableSingleRuntimeChunk()

  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  // enables hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())

  // Enable CSS minification in production
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

// UX Leaflet bridge imports leaflet.min.css; Leaflet 1.9 ships leaflet.css only.
const webpackConfig = Encore.getWebpackConfig();
webpackConfig.resolve = webpackConfig.resolve || {};
webpackConfig.resolve.alias = {
  ...(webpackConfig.resolve.alias || {}),
  "leaflet/dist/leaflet.min.css": path.resolve(
    __dirname,
    "node_modules/leaflet/dist/leaflet.css"
  ),
};

module.exports = webpackConfig;

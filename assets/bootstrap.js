import { startStimulusApp } from "@symfony/stimulus-bridge";

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
// Only load core controllers that are needed on every page
// Page-specific controllers are loaded via separate entry points (map.js, gallery.js, rock.js, filter.js)
const app = startStimulusApp(
  require.context(
    "@symfony/stimulus-bridge/lazy-controller-loader!./controllers",
    true,
    // Only include core controllers needed on every page
    /\/(cookie-consent|scroll[_-]progress|scrolltop|theme|autocomplete)_controller\.[jt]sx?$/
  )
);

// Disable Stimulus debug messages in console
app.debug = false;

// Expose Stimulus app globally for page-specific entry points
window.Stimulus = app;

export { app };

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

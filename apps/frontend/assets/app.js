/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Tailwind (app.css) then site SCSS (legacy + third-party) — one bundle avoids split CSS ordering issues.
import "./styles/app.css";
import "vanilla-cookieconsent/dist/cookieconsent.css";
import "./styles/global.scss";

// Symfony Stimulus bridge (assets/bootstrap.js — not the Bootstrap CSS framework)
import { app } from "./bootstrap";

// The area listing keeps Leaflet out of its initial download. Register the
// area-map controller and its CSS only when the visitor opens the map.
let areaMapLoad;
let rockMapLoad;
window.MunichMapLoader = {
  loadAreaMap() {
    areaMapLoad ??= import(/* webpackChunkName: "area-map-lazy" */ "./map/lazy-area-map");
    return areaMapLoad;
  },
  loadRockMap() {
    rockMapLoad ??= import(/* webpackChunkName: "rock-map-lazy" */ "./map/lazy-rock-map");
    return rockMapLoad;
  },
};

document.addEventListener("accordion:opened", (event) => {
  if (event.target?.dataset?.lazyModule === "rock-map") {
    void window.MunichMapLoader.loadRockMap().catch((error) => {
      console.error("Unable to load rock map", error);
    });
  }
});

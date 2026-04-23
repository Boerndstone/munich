/**
 * Map entry point - only loaded on pages with maps
 * Symfony UX Map (Leaflet) + filter controllers + weather
 */
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import { Application } from "@hotwired/stimulus";
import MapController from "./controllers/map_controller";
import MainMapController from "./controllers/main_map_controller";
import WeatherController from "./controllers/weather_controller";

const application = window.Stimulus;
if (!application) {
  throw new Error("map.js: load app.js first so window.Stimulus exists.");
}

application.register(
  "symfony--ux-leaflet-map--map",
  UxLeafletMapController.default ?? UxLeafletMapController
);
application.register("index-areas-map-filters", IndexAreasMapFiltersController);
application.register("area-overview-map-filters", AreaOverviewMapFiltersController);
application.register("weather", WeatherController);

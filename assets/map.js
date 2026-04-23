/**
 * Map entry point - only loaded on pages with maps
 * Symfony UX Map (Leaflet) + filter controllers + weather
 */
import UxLeafletMapController from "./controllers/symfony_ux_leaflet_map_safe_controller";
import IndexAreasMapFiltersController from "./controllers/index_areas_map_filters_controller";
import AreaOverviewMapFiltersController from "./controllers/area_overview_map_filters_controller";
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

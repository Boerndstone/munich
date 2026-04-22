/**
 * Map entry point - only loaded on pages with maps
 * Symfony UX Map (Leaflet) + filter controllers + weather
 */
import { Application } from "@hotwired/stimulus";
import UxLeafletMapController from "../vendor/symfony/ux-leaflet-map/assets/dist/map_controller.js";
import IndexAreasMapFiltersController from "./controllers/index_areas_map_filters_controller";
import AreaOverviewMapFiltersController from "./controllers/area_overview_map_filters_controller";
import WeatherController from "./controllers/weather_controller";

const application = window.Stimulus || (window.Stimulus = Application.start());

application.register(
  "symfony--ux-leaflet-map--map",
  UxLeafletMapController.default ?? UxLeafletMapController
);
application.register("index-areas-map-filters", IndexAreasMapFiltersController);
application.register("area-overview-map-filters", AreaOverviewMapFiltersController);
application.register("weather", WeatherController);

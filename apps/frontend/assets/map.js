/**
 * Map entry point - only loaded on pages with maps
 * Contains Leaflet-based controllers and weather
 */
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import { app } from "./bootstrap";
import MapController from "./controllers/map_controller";
import MainMapController from "./controllers/main_map_controller";
import WeatherController from "./controllers/weather_controller";

// Reuse the shared Stimulus app initialized by the main frontend entry.
const application = window.Stimulus || app;

// Register map controllers
application.register("map", MapController);
application.register("main-map", MainMapController);
application.register("weather", WeatherController);

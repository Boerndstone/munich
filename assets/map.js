/**
 * Map entry point - only loaded on pages with maps
 * Contains Leaflet-based controllers and weather
 */
import { Application } from "@hotwired/stimulus";
import MapController from "./controllers/map_controller";
import MainMapController from "./controllers/main_map_controller";
import WeatherController from "./controllers/weather_controller";

// Get or create the Stimulus application
const application = window.Stimulus || (window.Stimulus = Application.start());

// Register map controllers
application.register("map", MapController);
application.register("main-map", MainMapController);
application.register("weather", WeatherController);
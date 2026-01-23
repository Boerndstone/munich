/**
 * Route params entry point - lightweight bundle for pages showing route ratings
 * Used on suche.html.twig and rocks.html.twig (via _modal-top100-routes.html.twig)
 */
import { Application } from "@hotwired/stimulus";
import RouteparamsController from "./controllers/routeparams_controller";

// Get or create the Stimulus application
const application = window.Stimulus || Application.start();
window.Stimulus = application;

// Register controller
application.register("routeparams", RouteparamsController);

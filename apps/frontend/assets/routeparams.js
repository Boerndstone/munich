/**
 * Route params entry point - lightweight bundle for pages showing route ratings
 * Used on rocks.html.twig (via _modal-top100-routes.html.twig)
 */
import { app } from "./bootstrap";
import RouteparamsController from "./controllers/routeparams_controller";

// Reuse the shared Stimulus app initialized by the main frontend entry.
const application = window.Stimulus || app;

// Register controller
application.register("routeparams", RouteparamsController);

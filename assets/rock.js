/**
 * Rock detail page entry point
 * Contains route-related controllers
 */
import { Application } from "@hotwired/stimulus";
import RouteInformationTooltipController from "./controllers/route-information-tooltip_controller";
import RouteparamsController from "./controllers/routeparams_controller";
import ModalRouteInformationController from "./controllers/modal-route-information_controller";
import TabsController from "./controllers/tabs_controller";

// Get or create the Stimulus application
const application = window.Stimulus || (window.Stimulus = Application.start());

// Register rock page controllers
application.register("route-information-tooltip", RouteInformationTooltipController);
application.register("routeparams", RouteparamsController);
application.register("modal-route-information", ModalRouteInformationController);
application.register("tabs", TabsController);

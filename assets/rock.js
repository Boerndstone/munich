/**
 * Rock detail page entry point
 * Contains route-related controllers
 */
import { Application } from "@hotwired/stimulus";
import RouteInformationTooltipController from "./controllers/route-information-tooltip_controller";
import RouteparamsController from "./controllers/routeparams_controller";
import ModalRouteInformationController from "./controllers/modal-route-information_controller";
import TabsController from "./controllers/tabs_controller";
import AutoDismissAlertController from "./controllers/auto_dismiss_alert_controller";
import FlashDismissController from "./controllers/flash_dismiss_controller";

// Get or create the Stimulus application
const application = window.Stimulus || (window.Stimulus = Application.start());

// Register rock page controllers
application.register("route-information-tooltip", RouteInformationTooltipController);
application.register("routeparams", RouteparamsController);
application.register("modal-route-information", ModalRouteInformationController);
application.register("tabs", TabsController);
application.register("auto-dismiss-alert", AutoDismissAlertController);
application.register("flash-dismiss", FlashDismissController);

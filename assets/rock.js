/**
 * Rock detail page entry point
 * Contains route-related controllers
 */
import UxLeafletMapController from "./controllers/symfony_ux_leaflet_map_safe_controller";
import RockAccordionMapController from "./controllers/rock_accordion_map_controller";
import RockMapAccordionMountController from "./controllers/rock_map_accordion_mount_controller";
import RouteInformationTooltipController from "./controllers/route-information-tooltip_controller";
import RouteparamsController from "./controllers/routeparams_controller";
import ModalRouteInformationController from "./controllers/modal-route-information_controller";
import RockTopoTabsController from "./controllers/rock_topo_tabs_controller";
import AutoDismissAlertController from "./controllers/auto_dismiss_alert_controller";
import FlashDismissController from "./controllers/flash_dismiss_controller";
import AccordionController from "./controllers/accordion_controller";

const application = window.Stimulus;
if (!application) {
  throw new Error("rock.js: load app.js first so window.Stimulus exists.");
}

application.register(
  "symfony--ux-leaflet-map--map",
  UxLeafletMapController.default ?? UxLeafletMapController
);
application.register("rock-accordion-map", RockAccordionMapController);
application.register("rock-map-accordion-mount", RockMapAccordionMountController);

// Register rock page controllers
application.register("route-information-tooltip", RouteInformationTooltipController);
application.register("routeparams", RouteparamsController);
application.register("modal-route-information", ModalRouteInformationController);
application.register("rock-topo-tabs", RockTopoTabsController);
application.register("auto-dismiss-alert", AutoDismissAlertController);
application.register("flash-dismiss", FlashDismissController);
application.register("accordion", AccordionController);

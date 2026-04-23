import BaseUxLeafletMapController from "../../vendor/symfony/ux-leaflet-map/assets/dist/map_controller.js";

const Base = BaseUxLeafletMapController.default ?? BaseUxLeafletMapController;

/**
 * Vendor map controller never calls {@link L.Map#remove}; without it, Turbo / Stimulus
 * reconnect hits "Map container is already initialized" on the same DOM node.
 * Also avoid registering this twice: disable `@symfony/ux-leaflet-map` in controllers.json
 * when loading it from map.js / rock.js.
 */
export default class extends Base {
  disconnect() {
    try {
      if (this.map && typeof this.map.remove === "function") {
        this.map.remove();
      }
    } catch (_) {
      // DOM may already be detached
    }
    this.map = null;
    this.isConnected = false;
    super.disconnect();
  }
}

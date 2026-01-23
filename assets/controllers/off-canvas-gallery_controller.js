import { Controller } from "stimulus";
import lightGallery from "lightgallery";
import lgThumbnail from "lightgallery/plugins/thumbnail";
import lgZoom from "lightgallery/plugins/zoom";

export default class extends Controller {
  static targets = ["lgItem"];

  connect() {
    const container = this.element;

    lightGallery(container, {
      selector: ".lg-item",
      plugins: [lgZoom, lgThumbnail],
      licenseKey: "162AFA5B-3E30-4993-830C-377547A29E8B",
    });
  }
}

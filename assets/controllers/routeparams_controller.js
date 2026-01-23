import { Controller } from "stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["rating", "protection", "route-rock-quality"];

  connect() {
    const ratingValue = parseInt(this.data.get("rating"), 10);
    const rockQuality = this.data.get("route-rock-quality");
    const protectionValue = this.data.get("protection");
    if (ratingValue === -1) {
      this.ratingTarget.innerHTML = '<div class="trash d-inline-block"></div>';
    } else if (ratingValue > 0) {
      this.ratingTarget.innerHTML =
        '<div class="star d-inline-block"></div>'.repeat(ratingValue);
    } else {
      this.ratingTarget.innerHTML = "";
    }
    if (protectionValue == 2) {
      this.protectionTarget.innerHTML =
        '<div class="exclamation d-inline-block"></div>';
    } else if (protectionValue == 3) {
      this.protectionTarget.innerHTML =
        '<div class="skull d-inline-block"></div>';
    }
    if (rockQuality == 1) {
      this.protectionTarget.innerHTML =
        '<div class="loose-rock d-inline-block"></div>';
    }
  }
}

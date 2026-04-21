import { Controller } from "stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["rating", "protection", "route-rock-quality"];

  connect() {
    const ratingValue = parseInt(this.data.get("rating"), 10);
    const rockQuality = this.data.get("route-rock-quality");
    const protectionValue = this.data.get("protection");
    const icon = (cls) =>
      `<span class="${cls} inline-block align-middle" aria-hidden="true"></span>`;
    if (ratingValue === -1) {
      this.ratingTarget.innerHTML = icon("trash");
    } else if (ratingValue > 0) {
      this.ratingTarget.innerHTML = icon("star").repeat(ratingValue);
    } else {
      this.ratingTarget.innerHTML = "";
    }
    if (protectionValue == 2) {
      this.protectionTarget.innerHTML = icon("exclamation");
    } else if (protectionValue == 3) {
      this.protectionTarget.innerHTML = icon("skull");
    }
    if (rockQuality == 1) {
      this.protectionTarget.innerHTML = icon("loose-rock");
    }
  }
}

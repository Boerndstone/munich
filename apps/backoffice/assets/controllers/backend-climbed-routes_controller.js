import { Controller } from "stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  connect() {
    this.updateClimbedRoutesCount();
    this.element.addEventListener("change", () => {
      this.updateClimbedRoutesCount();
    });
  }

  updateClimbedRoutesCount() {
    const areaId = this.element.value;
    fetch("/climbed-routes-count/" + (areaId || ""))
      .then((response) => response.json())
      .then((data) => {
        document.getElementById("climbedRoutesCount").textContent = data.count;
      });
  }
}

import { Controller } from "stimulus";

export default class extends Controller {
  static targets = ["childFriendly", "sunny", "rain", "train", "bike"];

  connect() {
    this.filterItems();
  }

  filterItems() {
    const showChildFriendly = this.childFriendlyTarget.checked;
    const showSunny = this.sunnyTarget.checked;
    const showRain = this.rainTarget.checked;
    const showTrain = this.trainTarget.checked;
    const showBike = this.bikeTarget.checked;
    let visibleCount = 0;

    document.querySelectorAll(".rock-item").forEach((item) => {
      const isChildFriendly = item.dataset.childFriendly === "true";
      const isSunny = item.dataset.rockSunny === "true";
      const isRain = item.dataset.rockRain === "true";
      const isTrain = item.dataset.rockTrain === "true";
      const isBike = item.dataset.rockBike === "true";

      let shouldShow = true;
      if (showChildFriendly && !isChildFriendly) {
        shouldShow = false;
      }
      if (showSunny && !isSunny) {
        shouldShow = false;
      }
      if (showRain && !isRain) {
        shouldShow = false;
      }
      // Train / bike: if both checked, show rocks with train OR bike; if one checked, filter by that only
      if (showTrain && showBike) {
        if (!(isTrain || isBike)) shouldShow = false;
      } else if (showTrain && !isTrain) {
        shouldShow = false;
      } else if (showBike && !isBike) {
        shouldShow = false;
      }

      item.style.display = shouldShow ? "" : "none";

      if (shouldShow) visibleCount++;
    });

    document.getElementById("resultsCount").textContent =
      visibleCount.toString();
  }
}

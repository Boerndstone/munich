import { Controller } from "@hotwired/stimulus";
import L from "leaflet";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["map"];
  static values = {
    markers: Array,
  };

  connect() {
    this.map = L.map(this.mapTarget).setView([48.74, 12.44], 7);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18,
    }).addTo(this.map);

    // Add markers
    this.markersValue.forEach((markerData) => {
      L.marker([markerData[0], markerData[1]])
        .bindPopup(markerData[2])
        .addTo(this.map);
    });

    // Resize map when modal is shown
    const modal = this.element.closest(".modal");
    if (modal) {
      modal.addEventListener("shown.bs.modal", () => {
        setTimeout(() => this.map.invalidateSize(), 40);
      });
    }
  }
}

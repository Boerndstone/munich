import { Controller } from "@hotwired/stimulus";
import L from "leaflet";

export default class extends Controller {
  static targets = ["map"];
  static values = {
    markers: Array,
    collapseId: String,
  };

  connect() {
    this.map = L.map(this.mapTarget).setView([48.74, 12.44], 7);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> <strong><a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a></strong>',
      maxZoom: 18,
      accessToken:
        "pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw",
    }).addTo(this.map);

    // Add markers
    this.markersValue.forEach((markerData) => {
      L.marker([markerData[0], markerData[1]])
        .bindPopup(markerData[2])
        .addTo(this.map);
    });

    // Handle map resize on collapse
    if (this.collapseIdValue) {
      const collapseElement = document.getElementById(this.collapseIdValue);
      if (collapseElement) {
        collapseElement.addEventListener("shown.bs.collapse", () => {
          setTimeout(() => {
            this.map.invalidateSize();
          }, 40);
        });
      }
    }
  }
}

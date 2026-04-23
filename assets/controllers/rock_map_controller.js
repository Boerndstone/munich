import { Controller } from "stimulus";
import L from "leaflet";

const PARKING_SVG =
  '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="32" viewBox="0 0 448 512"><path stroke="#fff" fill="#075985" d="M64 32C28.7 32 0 60.7 0 96v320c0 35.3 28.7 64 64 64h320c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64zm128 224h48c17.7 0 32-14.3 32-32s-14.3-32-32-32h-48zm48 64h-48v32c0 17.7-14.3 32-32 32s-32-14.3-32-32V168c0-22.1 17.9-40 40-40h72c53 0 96 43 96 96s-43 96-96 96"/></svg>';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  connect() {
    const raw = this.element.dataset.rockMapPayload;
    if (!raw) {
      return;
    }
    let markersRock;
    try {
      markersRock = JSON.parse(raw);
    } catch {
      return;
    }

    const lat = Number(markersRock[0]);
    const lng = Number(markersRock[1]);
    let zoom = Number(markersRock[4]);
    if (zoom === 0) {
      zoom = 17;
    }

    this.map = L.map(this.element, { zoomControl: false }).setView([lat, lng], zoom);

    L.control
      .zoom({
        zoomInText: "+",
        zoomOutText: "\u2212",
        zoomInTitle: "Zoom in",
        zoomOutTitle: "Zoom out",
      })
      .addTo(this.map);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18,
    }).addTo(this.map);

    const iconUrl = "data:image/svg+xml;base64," + btoa(PARKING_SVG);
    const greenIcon = new L.Icon({
      iconUrl,
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41],
    });

    const markerObject = markersRock[2];
    const popupHtml = markersRock[3];

    if (markerObject) {
      L.marker(markerObject[0].coordinates.slice().reverse(), { icon: greenIcon }).addTo(this.map);
      const nameMarker = L.marker(markerObject[2].coordinates.slice().reverse(), { icon: greenIcon }).addTo(this.map);
      nameMarker.bindPopup(popupHtml).openPopup();
      L.geoJSON(markerObject).addTo(this.map);
    } else {
      const pointCoordinates = [lng, lat];
      L.geoJSON({
        type: "Point",
        coordinates: pointCoordinates,
      }).addTo(this.map);
    }

    const rockMapDetails = document.getElementById("rockMapDetails");
    if (rockMapDetails) {
      this._invalidateMap = () => {
        setTimeout(() => this.map?.invalidateSize(), 40);
      };
      if (rockMapDetails instanceof HTMLDetailsElement) {
        this._onDetailsToggle = () => {
          if (rockMapDetails.open) {
            this._invalidateMap();
          }
        };
        rockMapDetails.addEventListener("toggle", this._onDetailsToggle);
      } else {
        this._openObserver = new MutationObserver(() => {
          if (rockMapDetails.getAttribute("data-open") === "true") {
            this._invalidateMap();
          }
        });
        this._openObserver.observe(rockMapDetails, {
          attributes: true,
          attributeFilter: ["data-open"],
        });
      }
    }
  }

  disconnect() {
    const rockMapDetails = document.getElementById("rockMapDetails");
    if (rockMapDetails && this._onDetailsToggle) {
      rockMapDetails.removeEventListener("toggle", this._onDetailsToggle);
    }
    this._openObserver?.disconnect();
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }
}

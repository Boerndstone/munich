import { Controller } from "stimulus";
import L from "leaflet";
import { createMapPinIcon } from "../map/icons.js";

function readParkingIconHtml() {
  const el = document.getElementById("rock-map-parking-icon-json");
  if (!el?.textContent) {
    return "";
  }
  try {
    return JSON.parse(el.textContent.trim());
  } catch {
    return "";
  }
}

function createParkingMapIcon(innerHtml) {
  if (!innerHtml) {
    return createMapPinIcon();
  }
  return L.divIcon({
    html: `<div class="rock-map-parking-marker">${innerHtml}</div>`,
    className: "",
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    popupAnchor: [0, -34],
  });
}

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  connect() {
    const parkingIconHtml = readParkingIconHtml();

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

    const markerObject = markersRock[2];
    const popupHtml = markersRock[3];

    if (markerObject) {
      L.geoJSON(markerObject).addTo(this.map);
      L.marker(markerObject[0].coordinates.slice().reverse(), {
        icon: createParkingMapIcon(parkingIconHtml),
        zIndexOffset: 550,
      }).addTo(this.map);
      const nameMarker = L.marker(markerObject[2].coordinates.slice().reverse(), {
        icon: createMapPinIcon(),
        zIndexOffset: 600,
      }).addTo(this.map);
      nameMarker.bindPopup(popupHtml).openPopup();
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

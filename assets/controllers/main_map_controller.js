import { Controller } from "@hotwired/stimulus";
import L from "leaflet";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["map", "filters", "filterBtn"];
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

    this.markerLayers = [];
    this.currentRange = "all";
    this.renderMarkers(this.getFilteredMarkers());

    if (this.hasFilterBtnTarget) {
      this.filterBtnTargets.forEach((btn) => {
        btn.addEventListener("click", (e) => this.applyFilter(e));
      });
    }

    const modal = this.element.closest(".modal");
    if (modal) {
      modal.addEventListener("shown.bs.modal", () => {
        setTimeout(() => this.map.invalidateSize(), 40);
      });
    }
  }

  getFilteredMarkers() {
    const markers = this.markersValue || [];
    const rangeRaw = (this.currentRange ?? "").toString().trim();
    if (!rangeRaw || rangeRaw === "all") {
      return markers;
    }

    let min = -Infinity;
    let max = Infinity;

    // Support formats:
    // - "min-max"  (e.g. "0-30")
    // - "min+"     (e.g. "120+")
    // - "value"    (e.g. "120" treated as exact match)
    if (rangeRaw.endsWith("+")) {
      const minStr = rangeRaw.slice(0, -1).trim();
      const parsedMin = Number(minStr);
      if (!Number.isFinite(parsedMin)) {
        return [];
      }
      min = parsedMin;
      max = Infinity;
    } else if (rangeRaw.includes("-")) {
      const [minStrRaw, maxStrRaw] = rangeRaw.split("-", 2);
      const minStr = minStrRaw.trim();
      const maxStr = maxStrRaw.trim();

      if (minStr !== "") {
        const parsedMin = Number(minStr);
        if (!Number.isFinite(parsedMin)) {
          return [];
        }
        min = parsedMin;
      }

      if (maxStr !== "") {
        const parsedMax = Number(maxStr);
        if (!Number.isFinite(parsedMax)) {
          return [];
        }
        max = parsedMax;
      }
    } else {
      // Single numeric value: treat as exact range
      const exact = Number(rangeRaw);
      if (!Number.isFinite(exact)) {
        return [];
      }
      min = exact;
      max = exact;
    }

    return markers.filter((m) => {
      const t = Array.isArray(m) ? m[4] : m.travelTimeMinutes;
      if (t == null) return false;
      const num = typeof t === "number" ? t : Number(t);
      if (!Number.isFinite(num)) return false;
      return num >= min && num < max;
    });
  }

  applyFilter(e) {
    const btn = e.currentTarget;
    const range = btn.dataset.range;
    if (!range) return;
    this.currentRange = range;
    this.filterBtnTargets.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
    this.renderMarkers(this.getFilteredMarkers());
  }

  renderMarkers(markers) {
    this.markerLayers.forEach((layer) => this.map.removeLayer(layer));
    this.markerLayers = [];
    markers.forEach((m) => {
      const lat = this._getCoord(m, 0);
      const lng = this._getCoord(m, 1);
      if (lat == null || lng == null || Number.isNaN(lat) || Number.isNaN(lng)) return;
      const popup = Array.isArray(m) ? (m[2] ?? "") : (m.popup ?? "");
      const marker = L.marker([lat, lng])
        .bindPopup(popup)
        .addTo(this.map);
      this.markerLayers.push(marker);
    });
  }

  _getCoord(m, index) {
    if (Array.isArray(m)) {
      const v = m[index];
      return v != null ? parseFloat(v) : NaN;
    }
    const key = index === 0 ? "lat" : "lng";
    const v = m[key];
    return v != null ? parseFloat(v) : NaN;
  }
}

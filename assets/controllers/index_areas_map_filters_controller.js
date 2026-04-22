import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["filterBtn"];

  connect() {
    this.map = null;
    this.leafletMarkers = [];
    this.markerMeta = [];
    this.currentRange = "all";
    this._onUxMapConnect = (e) => {
      if (!this.element.contains(e.target)) return;
      this.onMapConnect(e);
    };
    this.element.addEventListener("ux:map:connect", this._onUxMapConnect);
  }

  disconnect() {
    this.element.removeEventListener("ux:map:connect", this._onUxMapConnect);
    this._dialogOpenObserver?.disconnect();
  }

  onMapConnect(event) {
    this.map = event.detail.map;
    this.leafletMarkers = event.detail.markers ?? [];
    this.markerMeta = event.detail.extra?.markerMeta ?? [];
    this.applyTravelFilter();

    const dialog = this.element.closest("dialog");
    if (dialog) {
      this._dialogOpenObserver = new MutationObserver(() => {
        if (dialog.open) {
          setTimeout(() => this.map?.invalidateSize(), 40);
        }
      });
      this._dialogOpenObserver.observe(dialog, {
        attributes: true,
        attributeFilter: ["open"],
      });
    }
  }

  applyFilter(event) {
    const btn = event.currentTarget;
    const range = btn.dataset.range;
    if (!range) return;
    this.currentRange = range;
    this.filterBtnTargets.forEach((b) => {
      b.classList.remove("active");
      b.setAttribute("aria-pressed", "false");
    });
    btn.classList.add("active");
    btn.setAttribute("aria-pressed", "true");
    this.applyTravelFilter();
  }

  applyTravelFilter() {
    if (!this.map || !this.leafletMarkers.length) return;

    const rangeRaw = (this.currentRange ?? "").toString().trim();
    this.leafletMarkers.forEach((marker, i) => {
      const meta = this.markerMeta[i] ?? {};
      const t = meta.travelTimeMinutes;
      let show = true;
      if (rangeRaw && rangeRaw !== "all") {
        let min = -Infinity;
        let max = Infinity;
        if (rangeRaw.endsWith("+")) {
          const parsedMin = Number(rangeRaw.slice(0, -1).trim());
          if (Number.isFinite(parsedMin)) {
            min = parsedMin;
            max = Infinity;
          } else {
            show = false;
          }
        } else if (rangeRaw.includes("-")) {
          const [minStrRaw, maxStrRaw] = rangeRaw.split("-", 2);
          const minStr = minStrRaw.trim();
          const maxStr = maxStrRaw.trim();
          if (minStr !== "") {
            const parsedMin = Number(minStr);
            if (Number.isFinite(parsedMin)) min = parsedMin;
            else show = false;
          }
          if (maxStr !== "") {
            const parsedMax = Number(maxStr);
            if (Number.isFinite(parsedMax)) max = parsedMax;
            else show = false;
          }
        } else {
          const exact = Number(rangeRaw);
          if (Number.isFinite(exact)) {
            min = exact;
            max = exact;
          } else {
            show = false;
          }
        }
        if (show) {
          if (t == null) {
            show = false;
          } else {
            const num = typeof t === "number" ? t : Number(t);
            if (!Number.isFinite(num)) show = false;
            else show = num >= min && num < max;
          }
        }
      }
      if (show) marker.addTo(this.map);
      else this.map.removeLayer(marker);
    });
  }
}

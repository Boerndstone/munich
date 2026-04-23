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
    this._teardownDialogMapResize();
  }

  onMapConnect(event) {
    this._teardownDialogMapResize();
    this.map = event.detail.map;
    this.leafletMarkers = event.detail.markers ?? [];
    this.markerMeta = event.detail.extra?.markerMeta ?? [];
    this.applyTravelFilter();
    this._setupDialogMapResize();
  }

  /**
   * Leaflet reads the container size at init; in a closed <dialog> that is often wrong.
   * Requires `encore_entry_link_tags('map')` so Leaflet CSS (tiles, markers, panes) is actually loaded.
   */
  _scheduleInvalidateSizes() {
    const m = this.map;
    if (!m || !m._loaded) return;
    const dialog = this.element.closest("dialog");
    if (dialog && !dialog.open) return;

    m._sizeChanged = true;
    m.invalidateSize({ animate: false, pan: false });

    const el = m.getContainer?.();
    if (el && el.clientWidth >= 4 && el.clientHeight >= 4) {
      try {
        const c = m.getCenter();
        const z = m.getZoom();
        m.setView(c, z, { animate: false, reset: true });
      } catch (_) {
        /* keep invalidateSize */
      }
      m.eachLayer?.((layer) => {
        if (layer && typeof layer.redraw === "function") layer.redraw();
      });
    }
  }

  _runInvalidateBurst() {
    this._scheduleInvalidateSizes();
    requestAnimationFrame(() => this._scheduleInvalidateSizes());
    requestAnimationFrame(() => requestAnimationFrame(() => this._scheduleInvalidateSizes()));
    setTimeout(() => this._scheduleInvalidateSizes(), 0);
    setTimeout(() => this._scheduleInvalidateSizes(), 60);
    setTimeout(() => this._scheduleInvalidateSizes(), 180);
    setTimeout(() => this._scheduleInvalidateSizes(), 400);
    setTimeout(() => this._scheduleInvalidateSizes(), 700);
  }

  _setupDialogMapResize() {
    const dialog = this.element.closest("dialog");
    if (!dialog || !this.map) return;

    this._dialogForMap = dialog;
    this._invalidateMapSize = () => this._runInvalidateBurst();

    // Never remove these listeners on dialog close — otherwise the next open never invalidates
    // Leaflet (tiles stay broken). Teardown only runs from disconnect / onMapConnect.
    this._onDialogToggle = () => {
      if (!dialog.open) return;
      this._runInvalidateBurst();
    };
    dialog.addEventListener("toggle", this._onDialogToggle);

    this._dialogOpenObserver = new MutationObserver(() => {
      if (dialog.open) {
        this._runInvalidateBurst();
      }
    });
    this._dialogOpenObserver.observe(dialog, { attributes: true, attributeFilter: ["open"] });

    this._onDialogTransitionEnd = (e) => {
      if (e.target !== dialog || e.propertyName !== "opacity") return;
      if (dialog.open) this._runInvalidateBurst();
    };
    dialog.addEventListener("transitionend", this._onDialogTransitionEnd);

    if (dialog.open) {
      this._runInvalidateBurst();
    }

    const container = this.map.getContainer?.();
    if (container && typeof ResizeObserver !== "undefined") {
      this._mapResizeObserver = new ResizeObserver(() => {
        if (dialog.open && this.map) this._scheduleInvalidateSizes();
      });
      this._mapResizeObserver.observe(container);
    }
  }

  _teardownDialogMapResize() {
    if (this._dialogForMap) {
      if (this._onDialogToggle) {
        this._dialogForMap.removeEventListener("toggle", this._onDialogToggle);
      }
      if (this._onDialogTransitionEnd) {
        this._dialogForMap.removeEventListener("transitionend", this._onDialogTransitionEnd);
      }
    }
    this._dialogOpenObserver?.disconnect();
    this._dialogOpenObserver = null;
    this._dialogForMap = null;
    this._onDialogToggle = null;
    this._onDialogTransitionEnd = null;
    this._invalidateMapSize = null;
    this._mapResizeObserver?.disconnect();
    this._mapResizeObserver = null;
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

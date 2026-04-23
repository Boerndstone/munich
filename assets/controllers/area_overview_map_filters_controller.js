import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["layerBtn", "attrFilterBtn"];

  connect() {
    this.map = null;
    this.leafletMarkers = [];
    this.markerMeta = [];
    this.rocksLayerVisible = true;
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
    this.applyRockVisibility();
    this._setupDialogMapResize();
  }

  /** Load Leaflet CSS via `encore_entry_link_tags('map')` on rocks.html (see index map filters). */
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

  toggleLayer(event) {
    const btn = event.currentTarget;
    const layer = btn.dataset.layer;
    btn.classList.toggle("active");
    btn.setAttribute("aria-pressed", btn.classList.contains("active") ? "true" : "false");
    if (layer === "rocks") {
      this.rocksLayerVisible = btn.classList.contains("active");
      this.applyRockVisibility();
    } else {
      const visible = btn.classList.contains("active");
      this.leafletMarkers.forEach((marker, i) => {
        const meta = this.markerMeta[i] ?? {};
        const mLayer = meta.layer;
        if (layer === "railway" && mLayer === "railway") {
          if (visible) marker.addTo(this.map);
          else this.map.removeLayer(marker);
        }
        if (layer === "camping" && mLayer === "camping") {
          if (visible) marker.addTo(this.map);
          else this.map.removeLayer(marker);
        }
      });
    }
  }

  toggleAttrFilter(event) {
    const btn = event.currentTarget;
    btn.classList.toggle("active");
    btn.setAttribute("aria-pressed", btn.classList.contains("active") ? "true" : "false");
    this.applyRockVisibility();
  }

  applyRockVisibility() {
    if (!this.map || !this.leafletMarkers.length) return;

    const attrActive = {};
    this.attrFilterBtnTargets.forEach((btn) => {
      const attr = btn.dataset.attr;
      if (attr) attrActive[attr] = btn.classList.contains("active");
    });
    const anyAttrFilter = Object.values(attrActive).some((v) => v);

    this.leafletMarkers.forEach((marker, i) => {
      const meta = this.markerMeta[i] ?? {};
      const layer = meta.layer;
      if (layer === "railway" || layer === "camping") {
        return;
      }
      if (layer !== "rock" || !this.rocksLayerVisible) {
        this.map.removeLayer(marker);
        return;
      }
      const childFriendly = !!meta.childFriendly;
      const sunny = !!meta.sunny;
      const rain = !!meta.rain;
      const train = !!meta.train;
      const bike = !!meta.bike;
      let show = true;
      if (attrActive.childFriendly && !childFriendly) show = false;
      if (attrActive.sunny && !sunny) show = false;
      if (attrActive.rain && !rain) show = false;
      if (attrActive.train && attrActive.bike) {
        if (!(train && bike)) show = false;
      } else {
        if (attrActive.train && !train) show = false;
        if (attrActive.bike && !bike) show = false;
      }
      if (anyAttrFilter && !show) {
        this.map.removeLayer(marker);
        return;
      }
      if (!anyAttrFilter || show) marker.addTo(this.map);
    });
  }
}

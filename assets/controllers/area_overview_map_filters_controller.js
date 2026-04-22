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
    this._dialogOpenObserver?.disconnect();
  }

  onMapConnect(event) {
    this.map = event.detail.map;
    this.leafletMarkers = event.detail.markers ?? [];
    this.markerMeta = event.detail.extra?.markerMeta ?? [];
    this.applyRockVisibility();

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

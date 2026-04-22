import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    accordionItemId: { type: String, default: "rockMapDetails" },
  };

  connect() {
    this.map = null;
    this._onUxMapConnect = (e) => {
      if (!this.element.contains(e.target)) return;
      this.onMapConnect(e);
    };
    this.element.addEventListener("ux:map:connect", this._onUxMapConnect);
  }

  disconnect() {
    this.element.removeEventListener("ux:map:connect", this._onUxMapConnect);
    this._accordionObserver?.disconnect();
  }

  onMapConnect(event) {
    this.map = event.detail.map;
    const details = document.getElementById(this.accordionItemIdValue);
    if (!details || !this.map) return;

    const invalidate = () => {
      setTimeout(() => this.map?.invalidateSize(), 40);
    };

    if (details instanceof HTMLDetailsElement) {
      details.addEventListener("toggle", () => {
        if (details.open) invalidate();
      });
    } else {
      const obs = new MutationObserver(() => {
        if (details.getAttribute("data-open") === "true") invalidate();
      });
      obs.observe(details, { attributes: true, attributeFilter: ["data-open"] });
      this._accordionObserver = obs;
    }
  }
}

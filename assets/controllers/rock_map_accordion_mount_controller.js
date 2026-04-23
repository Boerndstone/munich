import { Controller } from "@hotwired/stimulus";

/**
 * Injects the UX Map from a <template> into the accordion only when the rock info dialog is open
 * and the "Map" row is expanded, so Leaflet never boots under display:none / 0fr. Tears the map
 * down again when that section closes.
 */
export default class extends Controller {
  static targets = ["mount", "source"];

  connect() {
    this._mounted = false;
    this._mountRetryTimer = null;
    this._mountAttempts = 0;
    this._dialogEl = this.element.closest("dialog");
    this._itemEl = this.element.closest('[data-slot="accordion-item"]');
    this._contentEl = this.element.closest('[data-slot="accordion-content"]');

    this._onLayoutChange = () => this._updateVisibility();

    if (this._dialogEl) {
      this._dialogEl.addEventListener("toggle", this._onLayoutChange);
    }
    if (this._itemEl) {
      this._accordionObserver = new MutationObserver(this._onLayoutChange);
      this._accordionObserver.observe(this._itemEl, { attributes: true, attributeFilter: ["data-open"] });
    }
    if (this._contentEl) {
      this._onContentTransitionEnd = (e) => {
        if (e.target !== this._contentEl) return;
        if (e.propertyName === "grid-template-rows") this._onLayoutChange();
      };
      this._contentEl.addEventListener("transitionend", this._onContentTransitionEnd);
    }
    if (this.hasMountTarget && typeof ResizeObserver !== "undefined") {
      this._mountResizeObserver = new ResizeObserver(() => this._onLayoutChange());
      this._mountResizeObserver.observe(this.mountTarget);
    }

    requestAnimationFrame(() => this._onLayoutChange());
  }

  disconnect() {
    if (this._dialogEl) {
      this._dialogEl.removeEventListener("toggle", this._onLayoutChange);
    }
    this._accordionObserver?.disconnect();
    this._accordionObserver = null;
    if (this._contentEl && this._onContentTransitionEnd) {
      this._contentEl.removeEventListener("transitionend", this._onContentTransitionEnd);
    }
    this._contentEl = null;
    this._onContentTransitionEnd = null;
    this._mountResizeObserver?.disconnect();
    this._mountResizeObserver = null;
    clearTimeout(this._mountRetryTimer);
    this._mountRetryTimer = null;
    this._dialogEl = null;
    this._itemEl = null;
    this._unmount();
  }

  _dialogUsable() {
    const d = this._dialogEl;
    return !d || d.open;
  }

  _accordionOpen() {
    const item = this._itemEl;
    return !item || item.getAttribute("data-open") === "true";
  }

  _updateVisibility() {
    if (!this.hasMountTarget || !this.hasSourceTarget) return;
    if (!this._dialogUsable() || !this._accordionOpen()) {
      if (this._mounted) this._unmount();
      return;
    }
    this._tryMount();
  }

  _unmount() {
    const had = this._mounted;
    clearTimeout(this._mountRetryTimer);
    this._mountRetryTimer = null;
    this._mountAttempts = 0;
    if (had) {
      this.element.dispatchEvent(new CustomEvent("rock-topo-map:cleared"));
    }
    if (this.hasMountTarget) {
      this.mountTarget.replaceChildren();
    }
    this._mounted = false;
  }

  _tryMount() {
    if (this._mounted) return;
    if (!this._dialogUsable() || !this._accordionOpen()) return;

    clearTimeout(this._mountRetryTimer);
    this._mountRetryTimer = null;

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        if (this._mounted) return;
        if (!this._dialogUsable() || !this._accordionOpen()) return;

        const el = this.mountTarget;
        const { width, height } = el.getBoundingClientRect();
        const boxOk = width >= 8 && height >= 8;
        const maxTries = 40;

        if (!boxOk && this._mountAttempts < maxTries) {
          this._mountAttempts += 1;
          this._mountRetryTimer = setTimeout(() => this._tryMount(), 100);
          return;
        }

        this._mountAttempts = 0;
        this.mountTarget.appendChild(this.sourceTarget.content.cloneNode(true));
        this._mounted = true;
      });
    });
  }
}

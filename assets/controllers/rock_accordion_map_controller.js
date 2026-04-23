import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */

/**
 * Topo map markup is injected when the Map accordion opens (see rock-map-accordion-mount).
 * Repairs handle resize / transition edge cases after Leaflet connects.
 */
export default class extends Controller {
  connect() {
    this.map = null;
    this._pendingRepairs = [];
    this._repairDebounceTimer = null;
    this._onUxMapConnect = (e) => {
      if (!this.element.contains(e.target)) return;
      this.onMapConnect(e);
    };
    this._onPolylineBeforeCreate = (e) => {
      if (!this.element.contains(e.target)) return;
      const def = e.detail?.definition;
      if (!def?.points?.length) return;
      def.bridgeOptions = {
        color: "#075985",
        weight: 4,
        opacity: 0.95,
        lineCap: "round",
        lineJoin: "round",
        ...def.bridgeOptions,
      };
    };
    this.element.addEventListener("ux:map:connect", this._onUxMapConnect);
    this.element.addEventListener("ux:map:polyline:before-create", this._onPolylineBeforeCreate);
    this._onTopoMapCleared = () => {
      this._teardownMapResize();
      this.map = null;
    };
    this.element.addEventListener("rock-topo-map:cleared", this._onTopoMapCleared);
  }

  disconnect() {
    this.element.removeEventListener("ux:map:connect", this._onUxMapConnect);
    this.element.removeEventListener("ux:map:polyline:before-create", this._onPolylineBeforeCreate);
    this.element.removeEventListener("rock-topo-map:cleared", this._onTopoMapCleared);
    this._clearPendingRepairs();
    clearTimeout(this._repairDebounceTimer);
    this._repairDebounceTimer = null;
    this._teardownMapResize();
  }

  onMapConnect(event) {
    this._teardownMapResize();
    this.map = event.detail.map;
    this._setupMapResize();
    this._repairAfterDelays([0, 200, 600]);
  }

  _clearPendingRepairs() {
    this._pendingRepairs.forEach(clearTimeout);
    this._pendingRepairs = [];
  }

  /** Run {@link #_repairLeafletView} once after each delay (ms). */
  _repairAfterDelays(delaysMs) {
    delaysMs.forEach((ms) => {
      const id = setTimeout(() => {
        const i = this._pendingRepairs.indexOf(id);
        if (i !== -1) this._pendingRepairs.splice(i, 1);
        this._repairLeafletView();
      }, ms);
      this._pendingRepairs.push(id);
    });
  }

  _scheduleRepairDebounced(delayMs = 120) {
    clearTimeout(this._repairDebounceTimer);
    this._repairDebounceTimer = setTimeout(() => {
      this._repairDebounceTimer = null;
      this._repairLeafletView();
    }, delayMs);
  }

  _invalidateMapSize() {
    this._repairLeafletView();
    this._repairAfterDelays([160, 400]);
  }

  _repairLeafletView() {
    const m = this.map;
    if (!m || !m._loaded) return;

    const dialog = this.element.closest("dialog");
    if (dialog && !dialog.open) return;

    const el = m.getContainer?.();
    if (!el) return;
    const w = el.clientWidth;
    const h = el.clientHeight;
    if (w < 4 || h < 4) return;

    // Drop cached dimensions so getSize() reads the real box (see Leaflet Map#getSize).
    m._sizeChanged = true;

    // pan:false avoids drift when invalidateSize runs repeatedly (Leaflet default is pan:true).
    m.invalidateSize({ animate: false, pan: false });

    try {
      const c = m.getCenter();
      const z = m.getZoom();
      m.setView(c, z, { animate: false, reset: true });
    } catch (_) {
      // keep invalidateSize result if setView fails
    }

    m.eachLayer?.((layer) => {
      if (layer && typeof layer.redraw === "function") {
        layer.redraw();
      }
    });
  }

  _setupMapResize() {
    if (!this.map) return;

    const dialog = this.element.closest("dialog");
    if (dialog) {
      this._dialogEl = dialog;
      this._onDialogToggle = () => {
        if (dialog.open) this._invalidateMapSize();
      };
      dialog.addEventListener("toggle", this._onDialogToggle);
      this._dialogOpenObserver = new MutationObserver(() => {
        if (dialog.open) this._invalidateMapSize();
      });
      this._dialogOpenObserver.observe(dialog, { attributes: true, attributeFilter: ["open"] });
      if (dialog.open) this._invalidateMapSize();
    }

    const item = this.element.closest('[data-slot="accordion-item"]');
    if (item) {
      this._accordionItem = item;
      this._onAccordionItemAttrs = () => {
        if (item.getAttribute("data-open") === "true") this._invalidateMapSize();
      };
      this._accordionItemObserver = new MutationObserver(this._onAccordionItemAttrs);
      this._accordionItemObserver.observe(item, { attributes: true, attributeFilter: ["data-open"] });
      if (item.getAttribute("data-open") === "true") this._invalidateMapSize();
    }

    const content = item?.querySelector?.('[data-slot="accordion-content"]');
    if (content) {
      this._accordionContentEl = content;
      this._onAccordionTransitionEnd = (e) => {
        if (e.target !== content) return;
        if (e.propertyName === "grid-template-rows") this._invalidateMapSize();
      };
      content.addEventListener("transitionend", this._onAccordionTransitionEnd);

      if (typeof ResizeObserver !== "undefined") {
        this._accordionContentResizeObserver = new ResizeObserver(() => {
          if (!this.map) return;
          if (dialog && !dialog.open) return;
          const r = content.getBoundingClientRect();
          if (r.height < 64) return;
          this._scheduleRepairDebounced(100);
        });
        this._accordionContentResizeObserver.observe(content);
      }
    }

    const container = this.map.getContainer?.();
    if (container && typeof ResizeObserver !== "undefined") {
      this._mapResizeObserver = new ResizeObserver(() => {
        if (!this.map) return;
        if (dialog && !dialog.open) return;
        this._scheduleRepairDebounced(80);
      });
      this._mapResizeObserver.observe(container);
    }

    if (container && typeof IntersectionObserver !== "undefined") {
      this._mapVisibilityObserver = new IntersectionObserver(
        (entries) => {
          for (const entry of entries) {
            if (entry.isIntersecting && entry.intersectionRatio > 0) {
              this._scheduleRepairDebounced(60);
            }
          }
        },
        { root: null, threshold: 0.01 }
      );
      this._mapVisibilityObserver.observe(container);
    }
  }

  _teardownMapResize() {
    this._clearPendingRepairs();
    clearTimeout(this._repairDebounceTimer);
    this._repairDebounceTimer = null;

    if (this._dialogEl && this._onDialogToggle) {
      this._dialogEl.removeEventListener("toggle", this._onDialogToggle);
    }
    this._dialogOpenObserver?.disconnect();
    this._dialogOpenObserver = null;
    this._dialogEl = null;
    this._onDialogToggle = null;

    this._accordionItemObserver?.disconnect();
    this._accordionItemObserver = null;
    this._accordionItem = null;
    this._onAccordionItemAttrs = null;

    if (this._accordionContentEl && this._onAccordionTransitionEnd) {
      this._accordionContentEl.removeEventListener("transitionend", this._onAccordionTransitionEnd);
    }
    this._accordionContentEl = null;
    this._onAccordionTransitionEnd = null;

    this._accordionContentResizeObserver?.disconnect();
    this._accordionContentResizeObserver = null;

    this._mapResizeObserver?.disconnect();
    this._mapResizeObserver = null;

    this._mapVisibilityObserver?.disconnect();
    this._mapVisibilityObserver = null;
  }
}

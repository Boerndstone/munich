import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  connect() {
    this.element.querySelectorAll('.stroke-behavior[id^="svg_"]').forEach((el) => {
      if (el.getAttribute("data-path-id")) {
        return;
      }
      if (el.getAttribute("pointer-events") === "none") {
        return;
      }
      const m = el.id.match(/^svg_(\d+)$/);
      if (m) {
        el.setAttribute("data-path-id", m[1]);
      }
    });

    const interactive = this.element.querySelectorAll("[data-path-id]");

    const pathIdMap = new Map();
    interactive.forEach((element) => {
      const pathId = element.getAttribute("data-path-id");
      if (!pathId) {
        return;
      }
      const existingElement = pathIdMap.get(pathId);
      if (!existingElement) {
        pathIdMap.set(pathId, element);
        return;
      }

      const isPreferred = element.classList.contains("route-path-hit");
      const existingIsPreferred = existingElement.classList.contains("route-path-hit");

      if (isPreferred && !existingIsPreferred) {
        pathIdMap.set(pathId, element);
      }
    });

    const pathIds = Array.from(pathIdMap.entries()).map(([pathId, element]) => ({
      pathId,
      element,
    }));

    const strokes = this.element.querySelectorAll(".stroke-behavior");
    const strokeElements = Array.from(strokes).map((element) => ({
      id: element.id,
      element,
    }));

    const tableElements = this.element.querySelectorAll("[data-route-id]");
    const routeInfo = Array.from(tableElements).map((element) => ({
      routeId: element.getAttribute("data-route-id"),
      info: element.getAttribute("data-route-information"),
    }));

    this._tooltipEl = null;
    let activeStrokeElement = null;

    const hideTooltip = () => {
      if (this._tooltipEl) {
        this._tooltipEl.remove();
        this._tooltipEl = null;
      }
    };

    const showTooltip = (anchorEl, text) => {
      hideTooltip();
      if (!text) return;
      const tip = document.createElement("div");
      tip.className = "route-path-tooltip fixed z-[10600] max-w-xs rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-900 shadow-lg dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100";
      tip.setAttribute("role", "tooltip");
      tip.textContent = text;
      document.body.appendChild(tip);
      const rect = anchorEl.getBoundingClientRect();
      const tw = tip.offsetWidth;
      const th = tip.offsetHeight;
      let left = rect.left + rect.width / 2 - tw / 2;
      left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));
      let top = rect.top - th - 8;
      if (top < 8) {
        top = rect.bottom + 8;
      }
      tip.style.left = `${left}px`;
      tip.style.top = `${top}px`;
      this._tooltipEl = tip;
    };

    pathIds.forEach((path) => {
      const route = routeInfo.find((r) => r.routeId === path.pathId);
      if (!route) {
        return;
      }
      path.element.setAttribute("data-info", route.info);

      path.element.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        const isOpen = this._tooltipEl && this._tooltipEl.isConnected;
        if (isOpen) {
          hideTooltip();
        } else {
          showTooltip(path.element, route.info);
        }

        const strokeEl = strokeElements.find(
          (stroke) => stroke.id === `svg_${path.pathId}`
        );
        if (strokeEl) {
          if (
            activeStrokeElement &&
            activeStrokeElement !== strokeEl.element
          ) {
            activeStrokeElement.style.stroke = "";
          }
          strokeEl.element.style.stroke =
            strokeEl.element.style.stroke === "white" ? "" : "white";
          activeStrokeElement =
            strokeEl.element.style.stroke === "white"
              ? strokeEl.element
              : null;
        }
      });
    });

    this._docClick = () => {
      hideTooltip();
      if (activeStrokeElement) {
        activeStrokeElement.style.stroke = "";
        activeStrokeElement = null;
      }
    };
    document.addEventListener("click", this._docClick);
  }

  disconnect() {
    document.removeEventListener("click", this._docClick);
    if (this._tooltipEl) {
      this._tooltipEl.remove();
      this._tooltipEl = null;
    }
  }
}

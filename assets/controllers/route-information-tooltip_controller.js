import { Controller } from "@hotwired/stimulus";
import { Tooltip } from "bootstrap";

export default class extends Controller {
  connect() {
    // Legacy topo SVG: coloured path has id svg_N but no data-path-id
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

    // De-duplicate by pathId and prefer .route-path-hit elements when multiple
    // elements share the same data-path-id (e.g. hit-path + circle + text).
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

      // Only replace if the new element is preferred and the existing one is not.
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

    let activeTooltip = null;
    let activeStrokeElement = null;

    pathIds.forEach((path) => {
      const route = routeInfo.find((r) => r.routeId === path.pathId);
      if (!route) {
        return;
      }
      path.element.setAttribute("data-info", route.info);
      const tooltip = new Tooltip(path.element, {
        title: route.info,
        trigger: "manual",
        placement: "top",
      });

      path.element.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (activeTooltip && activeTooltip !== tooltip) {
          activeTooltip.hide();
        }
        if (path.element.getAttribute("aria-describedby")) {
          tooltip.hide();
          activeTooltip = null;
        } else {
          tooltip.show();
          activeTooltip = tooltip;
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

    document.addEventListener("click", () => {
      if (activeTooltip) {
        activeTooltip.hide();
        activeTooltip = null;
      }
      if (activeStrokeElement) {
        activeStrokeElement.style.stroke = "";
        activeStrokeElement = null;
      }
    });
  }
}

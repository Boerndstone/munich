import { Controller } from "stimulus";
import { Tooltip } from "bootstrap";

export default class extends Controller {
  connect() {
    const svgElements = this.element.querySelectorAll(".tooltip-trigger");
    const pathIds = Array.from(svgElements).map((element) => ({
      pathId: element.getAttribute("data-path-id"),
      element: element,
    }));

    const strokes = this.element.querySelectorAll(".stroke-behavior");
    const strokeElements = Array.from(strokes).map((element) => ({
      id: element.id,
      element: element,
    }));

    const tableElements = this.element.querySelectorAll("[data-route-id]");
    const routeInfo = Array.from(tableElements).map((element) => ({
      routeId: element.getAttribute("data-route-id"),
      info: element.getAttribute("data-route-information"),
    }));

    let activeTooltip = null;
    let activeStrokeElement = null;

    pathIds.forEach((path) => {
      const route = routeInfo.find((route) => route.routeId === path.pathId);
      if (route) {
        path.element.setAttribute("data-info", route.info);
        const tooltip = new Tooltip(path.element, {
          title: route.info,
          trigger: "click",
          placement: "top",
        });

        path.element.addEventListener("click", (event) => {
          event.stopPropagation();
          if (activeTooltip && activeTooltip !== tooltip) {
            activeTooltip.hide();
          }
          if (tooltip._element.getAttribute("aria-describedby")) {
            tooltip.hide();
            activeTooltip = null;
          } else {
            tooltip.show();
            activeTooltip = tooltip;
          }

          const strokeElement = strokeElements.find(
            (stroke) => stroke.id === `svg_${path.pathId}`
          );
          if (strokeElement) {
            if (
              activeStrokeElement &&
              activeStrokeElement !== strokeElement.element
            ) {
              activeStrokeElement.style.stroke = "";
            }
            strokeElement.element.style.stroke =
              strokeElement.element.style.stroke === "white" ? "" : "white";
            activeStrokeElement =
              strokeElement.element.style.stroke === "white"
                ? strokeElement.element
                : null;
          }
        });
      }
    });

    document.addEventListener("click", () => {
      if (activeTooltip) {
        activeTooltip.hide();
        activeTooltip = null;
      }
      if (activeStrokeElement) {
        activeStrokeElement.style.stroke = ""; // Reset active stroke element color
        activeStrokeElement = null;
      }
    });
  }
}

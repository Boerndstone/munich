import { Controller } from "@hotwired/stimulus";
import {
  getGradeDisplayMode,
  labelForPreference,
} from "../js/climbing_grade_display";

function routeTooltipTextFromRow(tr) {
  if (!tr) return "";
  const name = tr.getAttribute("data-route-name") || "";
  const stored = tr.getAttribute("data-route-grade-stored") || "";
  const fixed = tr.getAttribute("data-route-grade-fixed") === "1";
  const grade = fixed ? stored : labelForPreference(stored, getGradeDisplayMode());
  const g = grade || stored;
  return g ? `${name} (${g})`.trim() : name.trim();
}

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

    const tableElements = this.element.querySelectorAll("tr[data-route-id]");
    /** @type {Map<string, HTMLElement>} */
    const routeRowByPathId = new Map();
    tableElements.forEach((rowEl) => {
      const id = rowEl.getAttribute("data-route-id");
      if (id != null && id !== "") {
        routeRowByPathId.set(id, rowEl);
      }
    });

    /** @type {Map<string, HTMLElement>} */
    const strokeElByPathId = new Map();
    strokeElements.forEach(({ id, element }) => {
      const m = id.match(/^svg_(\d+)$/);
      if (m) {
        strokeElByPathId.set(m[1], element);
      }
    });

    /** @type {Map<string, Element>} */
    const circleByPathId = new Map();
    this.element.querySelectorAll("circle[data-path-id]").forEach((el) => {
      const id = el.getAttribute("data-path-id");
      if (id) {
        circleByPathId.set(id, el);
      }
    });

    /** @type {Map<string, Element>} */
    const textByPathId = new Map();
    this.element.querySelectorAll("text[data-path-id]").forEach((el) => {
      const id = el.getAttribute("data-path-id");
      if (id) {
        textByPathId.set(id, el);
      }
    });

    /**
     * Active: white circle fill, number uses the route circle color.
     * Inactive: restore saved fills from data attributes.
     * @param {string} pathId
     * @param {boolean} active
     */
    const setNumberBadgeActive = (pathId, active) => {
      const circle = circleByPathId.get(pathId);
      const text = textByPathId.get(pathId);
      if (!circle) {
        return;
      }

      if (active) {
        if (!circle.dataset.topoSavedFill) {
          circle.dataset.topoSavedFill = circle.getAttribute("fill") || "";
        }
        if (text && !text.dataset.topoSavedFill) {
          text.dataset.topoSavedFill = text.getAttribute("fill") || "#ffffff";
        }
        const routeColor = circle.dataset.topoSavedFill || "";
        circle.setAttribute("fill", "#ffffff");
        if (text && routeColor) {
          text.setAttribute("fill", routeColor);
        }
      } else {
        const cFill = circle.dataset.topoSavedFill;
        if (cFill !== undefined && cFill !== "") {
          circle.setAttribute("fill", cFill);
        }
        delete circle.dataset.topoSavedFill;
        if (text) {
          const tFill = text.dataset.topoSavedFill;
          if (tFill !== undefined && tFill !== "") {
            text.setAttribute("fill", tFill);
          }
          delete text.dataset.topoSavedFill;
        }
      }
    };

    this._setNumberBadgeActive = setNumberBadgeActive;

    this._tooltipEl = null;
    /** @type {string|null} */
    this._tooltipPathId = null;
    let activeStrokeElement = null;

    const hideTooltip = () => {
      if (this._tooltipEl) {
        this._tooltipEl.remove();
        this._tooltipEl = null;
      }
      this._tooltipPathId = null;
    };

    /**
     * @param {string} pathId
     * @param {HTMLElement} anchorEl
     * @param {string} text
     */
    const showTooltip = (pathId, anchorEl, text) => {
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
      this._tooltipPathId = pathId;
    };

    const refreshPathDataInfo = () => {
      pathIds.forEach((path) => {
        const row = routeRowByPathId.get(path.pathId);
        const text = row ? routeTooltipTextFromRow(row) : "";
        path.element.setAttribute("data-info", text);
      });
    };
    refreshPathDataInfo();
    this._onGradeDisplay = () => refreshPathDataInfo();
    document.addEventListener("munich:grade-display", this._onGradeDisplay);

    /** Clicks on wide hit path, number circle, or label — all share data-path-id. */
    this._pathClickHandler = (event) => {
      const hit = event.target.closest("[data-path-id]");
      if (!hit || !this.element.contains(hit)) {
        return;
      }

      const pathId = hit.getAttribute("data-path-id");
      if (!pathId) {
        return;
      }

      const row = routeRowByPathId.get(pathId);
      if (!row) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      const tipText = routeTooltipTextFromRow(row);
      const strokeEl = strokeElByPathId.get(pathId);
      const isOpen = this._tooltipEl && this._tooltipEl.isConnected;
      const closingSamePath = isOpen && this._tooltipPathId === pathId;

      if (closingSamePath) {
        hideTooltip();
        setNumberBadgeActive(pathId, false);
        if (strokeEl) {
          strokeEl.style.stroke = "";
          if (activeStrokeElement === strokeEl) {
            activeStrokeElement = null;
          }
        }
        return;
      }

      const prevPathForBadge = isOpen ? this._tooltipPathId : null;

      if (activeStrokeElement) {
        activeStrokeElement.style.stroke = "";
        activeStrokeElement = null;
      }
      showTooltip(pathId, hit, tipText);
      if (prevPathForBadge && prevPathForBadge !== pathId) {
        setNumberBadgeActive(prevPathForBadge, false);
      }
      setNumberBadgeActive(pathId, true);
      if (strokeEl) {
        strokeEl.style.stroke = "white";
        activeStrokeElement = strokeEl;
      }
    };
    this.element.addEventListener("click", this._pathClickHandler);

    this._docClick = () => {
      const pid = this._tooltipPathId;
      hideTooltip();
      if (pid) {
        setNumberBadgeActive(pid, false);
      }
      if (activeStrokeElement) {
        activeStrokeElement.style.stroke = "";
        activeStrokeElement = null;
      }
    };
    document.addEventListener("click", this._docClick);
  }

  disconnect() {
    document.removeEventListener("munich:grade-display", this._onGradeDisplay);
    document.removeEventListener("click", this._docClick);
    if (this._pathClickHandler) {
      this.element.removeEventListener("click", this._pathClickHandler);
      this._pathClickHandler = null;
    }
    if (this._tooltipPathId && this._setNumberBadgeActive) {
      this._setNumberBadgeActive(this._tooltipPathId, false);
    }
    if (this._tooltipEl) {
      this._tooltipEl.remove();
      this._tooltipEl = null;
    }
    this._tooltipPathId = null;
    this._setNumberBadgeActive = null;
  }
}

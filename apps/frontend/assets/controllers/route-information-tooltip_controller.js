import { Controller } from "@hotwired/stimulus";
import {
  getGradeDisplayMode,
  labelForPreference,
} from "../js/climbing_grade_display";

function routeTooltipTextFromRow(tr) {
  if (!tr) return "";
  const name = tr.getAttribute("data-route-name") || "";
  const grade = routeGradeFromRow(tr);
  return grade ? `${name} (${grade})`.trim() : name.trim();
}

function routeGradeFromRow(tr) {
  if (!tr) return "";
  const stored = tr.getAttribute("data-route-grade-stored") || "";
  const fixed = tr.getAttribute("data-route-grade-fixed") === "1";
  return fixed ? stored : labelForPreference(stored, getGradeDisplayMode()) || stored;
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
    const badgeTopByPathId = new Map();
    this.element.querySelectorAll("rect.number-square-top[data-path-id], circle[data-path-id]").forEach((el) => {
      const id = el.getAttribute("data-path-id");
      if (id) {
        badgeTopByPathId.set(id, el);
      }
    });

    /** @type {Map<string, Element>} */
    const badgeGroupByPathId = new Map();
    this.element.querySelectorAll("g.route-number-badge[data-path-id]").forEach((el) => {
      const id = el.getAttribute("data-path-id");
      if (id) {
        badgeGroupByPathId.set(id, el);
      }
    });

    const resizeBadgesForTouch = () => {
      const svg = this.element.querySelector(".topo-two-layer svg");
      if (!svg || !window.matchMedia("(pointer: coarse)").matches) {
        badgeGroupByPathId.forEach((group) => group.removeAttribute("transform"));
        return;
      }

      const viewBoxWidth = svg.viewBox.baseVal.width;
      const renderedWidth = svg.getBoundingClientRect().width;
      if (!viewBoxWidth || !renderedWidth) return;

      const scale = renderedWidth / viewBoxWidth;
      const inverseScale = 1 / scale;
      badgeGroupByPathId.forEach((group) => {
        const x = Number(group.getAttribute("data-badge-x"));
        const y = Number(group.getAttribute("data-badge-y"));
        group.setAttribute(
          "transform",
          `translate(${x} ${y}) scale(${inverseScale}) translate(${-x} ${-y})`,
        );
      });
    };

    this._resizeBadgesForTouch = resizeBadgesForTouch;
    this._badgeScaleResizeObserver = typeof ResizeObserver === "undefined"
      ? null
      : new ResizeObserver(resizeBadgesForTouch);
    this.element.querySelectorAll(".topo-two-layer svg").forEach((svg) => {
      this._badgeScaleResizeObserver?.observe(svg);
    });
    window.addEventListener("resize", resizeBadgesForTouch);
    resizeBadgesForTouch();

    /** @type {Map<string, Element>} */
    const textByPathId = new Map();
    this.element.querySelectorAll("text.number-text[data-path-id]").forEach((el) => {
      const id = el.getAttribute("data-path-id");
      if (id) {
        textByPathId.set(id, el);
      }
    });

    /** @type {Map<string, Element>} */
    const gradeTextByPathId = new Map();
    this.element.querySelectorAll("text.grade-text[data-path-id]").forEach((el) => {
      const id = el.getAttribute("data-path-id");
      if (id) {
        gradeTextByPathId.set(id, el);
      }
    });

    /** Keep route numbers white while preserving the topo's grade-colored badge fill. */
    const setNumberBadgeAppearance = (pathId) => {
      const text = textByPathId.get(pathId);
      text?.setAttribute("fill", "#ffffff");
    };

    this._setNumberBadgeAppearance = setNumberBadgeAppearance;

    this._tooltipEl = null;
    this._tooltipRepositionHandler = null;
    /** @type {string|null} */
    this._tooltipPathId = null;
    let activeStrokeElement = null;

    const hideTooltip = () => {
      if (this._tooltipEl) {
        this._tooltipEl.remove();
        this._tooltipEl = null;
      }
      if (this._tooltipRepositionHandler) {
        document.removeEventListener("scroll", this._tooltipRepositionHandler, true);
        window.removeEventListener("resize", this._tooltipRepositionHandler);
        this._tooltipRepositionHandler = null;
      }
      this._tooltipPathId = null;
    };

    /**
     * @param {string} pathId
     * @param {HTMLElement} anchorEl
     * @param {string} text
     */
    const showTooltip = (pathId, anchorEl, row) => {
      hideTooltip();
      if (!row) return;
      const tip = document.createElement("div");
      tip.className = "route-path-tooltip fixed z-[10600] max-w-xs border border-gray-900 bg-white px-2.5 py-2 text-xs text-gray-900 shadow-lg dark:border-gray-300 dark:bg-gray-900 dark:text-gray-100";
      tip.setAttribute("role", "tooltip");

      const title = document.createElement("div");
      title.className = "font-semibold leading-snug";
      title.textContent = routeTooltipTextFromRow(row);
      tip.appendChild(title);

      const firstAscent = row.getAttribute("data-route-first-ascent")?.trim() || "";
      const rawFirstAscentYear = row.getAttribute("data-route-first-ascent-year")?.trim() || "";
      const firstAscentYear = rawFirstAscentYear === "0" ? "" : rawFirstAscentYear;
      if (firstAscent || firstAscentYear) {
        const details = document.createElement("div");
        details.className = "mt-1 border-t border-gray-300 pt-1 text-gray-700 dark:border-gray-600 dark:text-gray-300";
        const label = row.getAttribute("data-route-first-ascent-label") || "First ascent";
        details.textContent = `${label}: ${[firstAscent, firstAscentYear].filter(Boolean).join(" ")}`;
        tip.appendChild(details);
      }

      document.body.appendChild(tip);
      this._tooltipEl = tip;
      this._tooltipPathId = pathId;

      // The tooltip is fixed to the viewport, so recalculate its position as
      // the badge moves during page or nested-container scrolling.
      this._tooltipRepositionHandler = () => {
        if (!tip.isConnected || !anchorEl.isConnected) {
          hideTooltip();
          return;
        }
        const rect = anchorEl.getBoundingClientRect();
        const tw = tip.offsetWidth;
        const th = tip.offsetHeight;
        const left = Math.max(8, Math.min(rect.left + rect.width / 2 - tw / 2, window.innerWidth - tw - 8));
        const top = Math.max(8, rect.top - th - 8);
        tip.style.left = `${left}px`;
        tip.style.top = `${top}px`;
      };
      document.addEventListener("scroll", this._tooltipRepositionHandler, true);
      window.addEventListener("resize", this._tooltipRepositionHandler);
      this._tooltipRepositionHandler();
    };

    const refreshPathDataInfo = () => {
      pathIds.forEach((path) => {
        const row = routeRowByPathId.get(path.pathId);
        const text = row ? routeTooltipTextFromRow(row) : "";
        path.element.setAttribute("data-info", text);
        const gradeBadge = gradeTextByPathId.get(path.pathId);
        if (gradeBadge) {
          gradeBadge.textContent = row ? routeGradeFromRow(row) : "";
        }
      });
    };
    refreshPathDataInfo();
    this._onGradeDisplay = () => refreshPathDataInfo();
    document.addEventListener("munich:grade-display", this._onGradeDisplay);

    /** Clicks on wide hit path, number circle, or label — all share data-path-id. */
    this._pathClickHandler = (event) => {
      const target = event.target instanceof Element ? event.target : null;
      const hit = target?.closest("[data-path-id]");
      let pathId = hit && this.element.contains(hit)
        ? hit.getAttribute("data-path-id")
        : null;

      // Topo SVG coordinates scale down with the image, making the visible
      // badge too small to tap on phones. Give each badge a 48px screen-space
      // target without changing its rendered size.
      const clickedTopo = target?.closest(".topo-two-layer");
      if (clickedTopo && window.matchMedia("(pointer: coarse)").matches) {
        const x = event.clientX;
        const y = event.clientY;
        let nearestBadgePathId = null;
        let nearestBadgeDistance = 24;

        badgeTopByPathId.forEach((badge, badgePathId) => {
          const rect = badge.getBoundingClientRect();
          const centerX = rect.left + rect.width / 2;
          const centerY = badge.tagName.toLowerCase() === "circle"
            ? rect.top + rect.height / 2
            : rect.bottom;
          const distance = Math.max(Math.abs(x - centerX), Math.abs(y - centerY));
          if (distance <= nearestBadgeDistance) {
            nearestBadgeDistance = distance;
            nearestBadgePathId = badgePathId;
          }
        });

        if (nearestBadgePathId) {
          pathId = nearestBadgePathId;
        }
      }

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
        setNumberBadgeAppearance(pathId);
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
      showTooltip(pathId, badgeTopByPathId.get(pathId) || hit, row);
      if (prevPathForBadge && prevPathForBadge !== pathId) {
        setNumberBadgeAppearance(prevPathForBadge);
      }
      setNumberBadgeAppearance(pathId);
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
        setNumberBadgeAppearance(pid);
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
    window.removeEventListener("resize", this._resizeBadgesForTouch);
    this._badgeScaleResizeObserver?.disconnect();
    this._badgeScaleResizeObserver = null;
    this._resizeBadgesForTouch = null;
    if (this._tooltipPathId && this._setNumberBadgeAppearance) {
      this._setNumberBadgeAppearance(this._tooltipPathId);
    }
    if (this._tooltipEl) {
      this._tooltipEl.remove();
      this._tooltipEl = null;
    }
    if (this._tooltipRepositionHandler) {
      document.removeEventListener("scroll", this._tooltipRepositionHandler, true);
      window.removeEventListener("resize", this._tooltipRepositionHandler);
      this._tooltipRepositionHandler = null;
    }
    this._tooltipPathId = null;
    this._setNumberBadgeAppearance = null;
  }
}

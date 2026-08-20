import { Controller } from "@hotwired/stimulus";
import { Chart, registerables } from "chart.js";
import colors from "tailwindcss/colors";

Chart.register(...registerables);

const LABELS_GRADES = ["3", "4", "5", "6", "7", "8", "9", "10", "11"];
const GRADE_FALLBACKS = {
  3: "#059669",
  4: "#16a34a",
  5: "#65a30d",
  6: "#d97706",
  7: "#ea580c",
  8: "#c2410c",
  9: "#dc2626",
  10: "#e11d48",
  11: "#4338ca",
  project: "#334155",
};

function gradeToken(name, fallback) {
  const value = getComputedStyle(document.documentElement)
    .getPropertyValue(name)
    .trim();
  return value || fallback;
}

/** Grade bucket colors (index matches LABELS_GRADES). */
function gradeColorAt(index) {
  const label = LABELS_GRADES[index];
  if (!label) {
    return GRADE_FALLBACKS[11];
  }
  return gradeToken(`--grade-bucket-${label}`, GRADE_FALLBACKS[label]);
}

function colorsForLabels(labels) {
  return labels.map((label) => {
    if (label === "Proj.") {
      return gradeToken("--grade-project", GRADE_FALLBACKS.project);
    }
    const idx = LABELS_GRADES.indexOf(label);
    return gradeColorAt(idx >= 0 ? idx : 0);
  });
}

/**
 * Drop grade / Proj. buckets with count 0 so the chart stays narrow.
 */
function compactChartData(raw) {
  if (!Array.isArray(raw)) {
    return { labels: [], values: [] };
  }
  const withProjects = raw.length >= 10;
  const gradeSlice = raw.slice(0, 9);
  const proj = withProjects ? Number(raw[9]) || 0 : 0;
  const labels = [];
  const values = [];
  LABELS_GRADES.forEach((label, i) => {
    const v = Number(gradeSlice[i]) || 0;
    if (v > 0) {
      labels.push(label);
      values.push(v);
    }
  });
  if (withProjects && proj > 0) {
    labels.push("Proj.");
    values.push(proj);
  }
  return { labels, values };
}

/**
 * Y-axis step and ceiling: small counts use 1 / 5 / 10; from 51+ use steps of 50 (50, 100, …).
 */
function yAxisScale(dataMax) {
  const d = Math.max(Number(dataMax) || 0, 1);
  if (d <= 10) {
    return { stepSize: 1, scaleMax: d };
  }
  if (d <= 20) {
    return { stepSize: 5, scaleMax: Math.ceil(d / 5) * 5 };
  }
  if (d <= 50) {
    return { stepSize: 10, scaleMax: Math.ceil(d / 10) * 10 };
  }
  return { stepSize: 50, scaleMax: Math.ceil(d / 50) * 50 };
}

function ariaSummary(prefix, ariaTotalPrefix, labels, values) {
  const parts = labels.map((lab, i) => {
    if (lab === "Proj.") {
      return `Projekte: ${values[i]}`;
    }
    return `Grad ${lab}: ${values[i]}`;
  });
  const body = parts.length ? parts.join("; ") : "Keine Routen in den angezeigten Stufen.";
  return `${prefix}${ariaTotalPrefix || ""}Routen je Gradstufe. ${body}`;
}

/** Draws numeric value centered above each bar (Chart.js 3). */
const gradeBarValueLabelsPlugin = {
  id: "gradeBarValueLabels",
  afterDatasetsDraw(chart) {
    const cfg = chart.options.plugins?.gradeBarValueLabels;
    if (cfg?.display === false) {
      return;
    }
    const color = cfg?.color ?? "#000000";
    const fontSize = cfg?.fontSize ?? 9;
    const { ctx } = chart;
    const meta = chart.getDatasetMeta(0);
    if (!meta?.data?.length) {
      return;
    }
    const values = chart.data.datasets[0].data;
    ctx.save();
    ctx.textAlign = "center";
    ctx.textBaseline = "bottom";
    ctx.font = `${fontSize}px system-ui, -apple-system, sans-serif`;
    ctx.fillStyle = color;
    meta.data.forEach((bar, i) => {
      const value = values[i];
      const n = Number(value) || 0;
      const { x, y, base } = bar.getProps(["x", "y", "base"], true);
      const top = Math.min(y, base);
      ctx.fillText(String(n), x, top - 2);
    });
    ctx.restore();
  },
};

Chart.register(gradeBarValueLabelsPlugin);

/**
 * Grades 3–11 plus optional Proj.; only non-zero buckets are shown.
 */
export default class extends Controller {
  static values = {
    counts: Array,
    ariaPrefix: { type: String, default: "" },
    /** Localised "Total N routes" fragment for aria-label (from Twig). */
    ariaTotalPrefix: { type: String, default: "" },
    /** When true (e.g. index map rock popup), hide the Y-axis amount scale; values stay on bars + aria. */
    hideYAxis: { type: Boolean, default: false },
  };

  connect() {
    this.canvas = this.element.querySelector("canvas");
    if (!this.canvas) {
      return;
    }
    this.destroyCanvasChart();
    this.ctx = this.canvas.getContext("2d");
    if (!this.ctx) {
      return;
    }
    this.renderFromCounts();
  }

  renderFromCounts() {
    const raw = Array.isArray(this.countsValue)
      ? this.countsValue.map((n) => Number(n) || 0)
      : [];

    const { labels, values } = compactChartData(raw);
    this.syncAria(labels, values);

    if (labels.length === 0) {
      if (this.chart) {
        this.chart.destroy();
        this.chart = null;
      }
      this.canvas.style.display = "none";
      this.element.removeAttribute("aria-hidden");
      this.element.setAttribute(
        "aria-label",
        ariaSummary(
          this.ariaPrefixValue || "",
          this.ariaTotalPrefixValue || "",
          [],
          []
        )
      );
      return;
    }

    this.element.removeAttribute("aria-hidden");
    this.canvas.style.display = "";

    const dataMax = Math.max(...values, 1);
    const { stepSize, scaleMax } = yAxisScale(dataMax);
    const dark = this.isDark();
    const labelInk = dark ? colors.zinc[100] : "#000000";
    const yAxisLine = dark ? "rgba(244, 244, 245, 0.14)" : "rgba(0, 0, 0, 0.08)";
    const yTickColor = dark ? "rgba(244, 244, 245, 0.55)" : "rgba(0, 0, 0, 0.45)";
    const hideY = this.hideYAxisValue === true;

    this.destroyCanvasChart();
    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }

    this.chart = new Chart(this.ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            data: values,
            backgroundColor: colorsForLabels(labels),
            borderWidth: 0,
            borderRadius: 4,
            barPercentage: 0.85,
            categoryPercentage: 0.9,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        layout: { padding: { top: 14, bottom: 0, left: 0, right: 0 } },
        plugins: {
          legend: { display: false },
          tooltip: { enabled: false },
          gradeBarValueLabels: {
            color: labelInk,
            fontSize: 9,
          },
        },
        scales: {
          x: {
            display: true,
            grid: { display: false },
            ticks: {
              font: { size: 9 },
              color: labelInk,
              maxRotation: 0,
            },
          },
          y: {
            display: !hideY,
            min: 0,
            max: scaleMax,
            beginAtZero: true,
            ticks: {
              display: !hideY,
              stepSize,
              precision: 0,
              color: yTickColor,
              font: { size: 8 },
              padding: 4,
              maxTicksLimit: 20,
            },
            grid: {
              display: false,
              drawTicks: !hideY,
              tickLength: 4,
              tickWidth: 1,
              tickColor: yAxisLine,
              drawOnChartArea: false,
              drawBorder: !hideY,
              borderWidth: 1,
              borderColor: yAxisLine,
            },
          },
        },
      },
    });
  }

  disconnect() {
    this.destroyCanvasChart();
    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }
  }

  destroyCanvasChart() {
    if (!this.canvas) {
      return;
    }
    const existing = Chart.getChart(this.canvas);
    if (existing) {
      existing.destroy();
    }
  }

  syncAria(labels, values) {
    this.element.setAttribute(
      "aria-label",
      ariaSummary(
        this.ariaPrefixValue || "",
        this.ariaTotalPrefixValue || "",
        labels,
        values
      )
    );
  }

  countsValueChanged() {
    if (!this.canvas || !this.ctx || !Array.isArray(this.countsValue)) {
      return;
    }
    this.renderFromCounts();
  }

  ariaPrefixValueChanged() {
    this.refreshAriaOnly();
  }

  ariaTotalPrefixValueChanged() {
    this.refreshAriaOnly();
  }

  refreshAriaOnly() {
    if (!Array.isArray(this.countsValue)) {
      return;
    }
    const raw = this.countsValue.map((n) => Number(n) || 0);
    const { labels, values } = compactChartData(raw);
    this.syncAria(labels, values);
  }

  isDark() {
    return document.documentElement.classList.contains("dark");
  }
}

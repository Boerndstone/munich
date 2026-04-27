/**
 * UIAA ↔ French free-climbing display labels (Bergfreunde “Climbing Grades Compared”).
 * Duplicated from PHP {@link App\Service\GradeTranslationService::BERGFREUNDE_CLIMBING_GRADES_COMPARED}
 * — keep in sync until a build step or shared source exists.
 *
 * @see https://www.bergfreunde.eu/climbing-grade-calculator/
 */

/** @typedef {{ uiaa: string, french: string }} BergRow */

/** @type {readonly BergRow[]} */
export const BERGFREUNDE_CLIMBING_GRADES_COMPARED = Object.freeze([
  { uiaa: "1", french: "1" },
  { uiaa: "2", french: "2" },
  { uiaa: "3", french: "3" },
  { uiaa: "4", french: "4" },
  { uiaa: "4+", french: "4+" },
  { uiaa: "5-", french: "5a" },
  { uiaa: "5", french: "5a/5b" },
  { uiaa: "5+", french: "5b" },
  { uiaa: "6-", french: "5b/5c" },
  { uiaa: "6", french: "5c" },
  { uiaa: "6+", french: "6a" },
  { uiaa: "7-", french: "6a+" },
  { uiaa: "7", french: "6b" },
  { uiaa: "7+", french: "6b+" },
  { uiaa: "7+/8-", french: "6c" },
  { uiaa: "8-", french: "6c+" },
  { uiaa: "8", french: "7a" },
  { uiaa: "8+", french: "7a+" },
  { uiaa: "8+/9-", french: "7b" },
  { uiaa: "9-", french: "7b+" },
  { uiaa: "9", french: "7c" },
  { uiaa: "9+", french: "7c+" },
  { uiaa: "9+/10-", french: "8a" },
  { uiaa: "10-", french: "8a/8a+" },
  { uiaa: "10-", french: "8a+" },
  { uiaa: "10", french: "8b" },
  { uiaa: "10+", french: "8b+" },
  { uiaa: "10+/11-", french: "8c" },
  { uiaa: "11-", french: "8c+" },
  { uiaa: "11", french: "9a" },
  { uiaa: "11", french: "9a/9a+" },
  { uiaa: "11/11+", french: "9a+" },
  { uiaa: "11+", french: "9a+/9b" },
  { uiaa: "11+/12-", french: "9b" },
  { uiaa: "12-", french: "9b+" },
  { uiaa: "12", french: "9c" },
]);

export const GRADE_DISPLAY_STORAGE_KEY = "gradeDisplay";

/** @param {string} value */
export function normalizeBergfreundeGradeCell(value) {
  let v = String(value).replace(/\\>/g, ">");
  v = v.replace(/\s*\/\s*/g, "/");
  v = v.trim().replace(/\s+/g, " ");
  return v;
}

/** @param {string|null|undefined} stored */
export function isFontBoulderGrade(stored) {
  if (stored == null || stored === "") return false;
  const t = String(stored).trim().toUpperCase();
  return t.startsWith("FB ");
}

/** Hueco-style boulder: pass-through for UIAA/French switch */
/** @param {string|null|undefined} stored */
export function isHuecoStyleGrade(stored) {
  if (stored == null || stored === "") return false;
  const t = String(stored).trim();
  return /^(VB|V\d)/i.test(t);
}

/** @returns {"uiaa"|"french"} */
export function getGradeDisplayMode() {
  try {
    const raw = localStorage.getItem(GRADE_DISPLAY_STORAGE_KEY);
    if (raw === "french" || raw === "uiaa") return raw;
  } catch {
    /* private mode */
  }
  return "uiaa";
}

/**
 * @param {"uiaa"|"french"} mode
 */
export function setGradeDisplayMode(mode) {
  const m = mode === "french" ? "french" : "uiaa";
  try {
    localStorage.setItem(GRADE_DISPLAY_STORAGE_KEY, m);
  } catch {
    /* ignore */
  }
  document.dispatchEvent(
    new CustomEvent("munich:grade-display", { detail: { mode: m } }),
  );
}

let _frenchNormToRow = null;
/** @type {Map<string, BergRow[]>} */
let _uiaaNormToRows = null;

function buildIndexes() {
  if (_frenchNormToRow !== null) return;
  /** @type {Map<string, BergRow>} */
  const frenchMap = new Map();
  /** @type {Map<string, BergRow[]>} */
  const uiaaMap = new Map();
  for (const row of BERGFREUNDE_CLIMBING_GRADES_COMPARED) {
    const u = normalizeBergfreundeGradeCell(row.uiaa);
    const f = normalizeBergfreundeGradeCell(row.french);
    if (f && !frenchMap.has(f)) {
      frenchMap.set(f, row);
    }
    if (u) {
      const list = uiaaMap.get(u) || [];
      list.push(row);
      uiaaMap.set(u, list);
    }
  }
  _frenchNormToRow = frenchMap;
  _uiaaNormToRows = uiaaMap;
}

/**
 * First matching row for normalized key (duplicate UIAA e.g. two "10-" rows: first wins without grade_no).
 * @param {string} normalized
 * @returns {BergRow|null}
 */
function lookupRowSingle(normalized) {
  buildIndexes();
  const f = _frenchNormToRow.get(normalized);
  if (f) return f;
  const rows = _uiaaNormToRows.get(normalized);
  if (rows && rows.length > 0) return rows[0];
  return null;
}

/**
 * @param {string|null|undefined} stored
 * @returns {{ uiaa: string, french: string }}
 */
export function climbingGradeDisplayPair(stored) {
  if (stored == null || stored === "") {
    return { uiaa: "", french: "" };
  }
  const raw = String(stored).trim();
  if (isFontBoulderGrade(raw) || isHuecoStyleGrade(raw)) {
    return { uiaa: raw, french: raw };
  }
  const n = normalizeBergfreundeGradeCell(raw.replace(/\s*\/\s*/g, "/"));
  if (!n) {
    return { uiaa: raw, french: raw };
  }

  const direct = lookupRowSingle(n);
  if (direct) {
    return {
      uiaa: normalizeBergfreundeGradeCell(direct.uiaa),
      french: normalizeBergfreundeGradeCell(direct.french),
    };
  }

  if (n.includes("/")) {
    const parts = n.split("/");
    const uiaaParts = [];
    const frenchParts = [];
    for (const part of parts) {
      const q = normalizeBergfreundeGradeCell(part);
      if (!q) continue;
      const sub = lookupRowSingle(q);
      if (sub) {
        uiaaParts.push(normalizeBergfreundeGradeCell(sub.uiaa));
        frenchParts.push(normalizeBergfreundeGradeCell(sub.french));
      } else {
        uiaaParts.push(q);
        frenchParts.push(q);
      }
    }
    if (uiaaParts.length > 0) {
      return { uiaa: uiaaParts.join("/"), french: frenchParts.join("/") };
    }
  }

  return { uiaa: raw, french: raw };
}

/**
 * @param {string|null|undefined} stored
 * @param {"uiaa"|"french"} mode
 */
export function labelForPreference(stored, mode) {
  const pair = climbingGradeDisplayPair(stored);
  return mode === "french" ? pair.french : pair.uiaa;
}

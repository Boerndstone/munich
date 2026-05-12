import { Controller } from "stimulus";
import {
  getGradeDisplayMode,
  labelForPreference,
} from "../js/climbing_grade_display";

export default class extends Controller {
  connect() {
    this._onGradeDisplay = () => this.applyLabels();
    document.addEventListener("munich:grade-display", this._onGradeDisplay);
    this.applyLabels();
  }

  disconnect() {
    document.removeEventListener("munich:grade-display", this._onGradeDisplay);
  }

  applyLabels() {
    const mode = getGradeDisplayMode();
    this.element.querySelectorAll("[data-route-grade-label]").forEach((el) => {
      if (el.getAttribute("data-route-grade-fixed") === "true") {
        return;
      }
      const stored = el.getAttribute("data-route-grade-stored") || "";
      el.textContent = labelForPreference(stored, mode);
    });
  }
}

import { Controller } from "stimulus";

export default class extends Controller {
  static targets = ["trigger", "sun", "moon"];

  connect() {
    const raw = localStorage.getItem("theme");
    const theme = raw === "dark" ? "dark" : "light";
    this.applyTheme(theme);
  }

  /** Sun ↔ moon: flip light / dark */
  toggle() {
    const next = document.documentElement.classList.contains("dark") ? "light" : "dark";
    this.applyTheme(next);
  }

  applyTheme(theme) {
    const t = theme === "dark" ? "dark" : "light";
    document.documentElement.setAttribute("data-theme", t);
    document.documentElement.classList.toggle("dark", t === "dark");
    localStorage.setItem("theme", t);
    this.updateIcons(t);
  }

  updateIcons(theme) {
    if (this.hasSunTarget && this.hasMoonTarget) {
      if (theme === "dark") {
        this.sunTarget.classList.add("hidden");
        this.moonTarget.classList.remove("hidden");
      } else {
        this.sunTarget.classList.remove("hidden");
        this.moonTarget.classList.add("hidden");
      }
    }
    if (this.hasTriggerTarget) {
      this.triggerTarget.setAttribute("aria-checked", theme === "dark" ? "true" : "false");
    }
  }
}

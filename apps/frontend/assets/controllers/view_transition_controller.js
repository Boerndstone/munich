import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  connect() {
    this.prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    this.applyDebugMode();
    this.handleBeforeRender = this.handleBeforeRender.bind(this);
    document.addEventListener("turbo:before-render", this.handleBeforeRender);
  }

  disconnect() {
    document.removeEventListener("turbo:before-render", this.handleBeforeRender);
    this.prefersReducedMotion = null;
  }

  handleBeforeRender(event) {
    if (!document.startViewTransition || this.prefersReducedMotion?.matches) {
      return;
    }

    const originalRender = event.detail.render;
    if (typeof originalRender !== "function") {
      return;
    }

    event.detail.render = (currentBody, newBody) =>
      document.startViewTransition(() => originalRender(currentBody, newBody));
  }

  applyDebugMode() {
    const params = new URLSearchParams(window.location.search);
    const enabled = params.get("vtdebug") === "1";

    if (enabled) {
      document.documentElement.dataset.viewTransitionDebug = "true";
    } else {
      delete document.documentElement.dataset.viewTransitionDebug;
    }
  }
}

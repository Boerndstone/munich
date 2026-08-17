import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    selector: { type: String, default: "a[data-prefetch-candidate]" },
    idleSelector: String,
    maxIdle: { type: Number, default: 6 },
  };

  connect() {
    this.prefetched = new Set();
    this.handlePointerOver = this.handlePointerOver.bind(this);
    this.handleFocusIn = this.handleFocusIn.bind(this);

    this.element.addEventListener("pointerover", this.handlePointerOver);
    this.element.addEventListener("focusin", this.handleFocusIn);

    this.scheduleIdlePrefetch();
  }

  disconnect() {
    this.element.removeEventListener("pointerover", this.handlePointerOver);
    this.element.removeEventListener("focusin", this.handleFocusIn);

    if (this.idleHandle) {
      if ("cancelIdleCallback" in window) {
        window.cancelIdleCallback(this.idleHandle);
      } else {
        window.clearTimeout(this.idleHandle);
      }
    }
  }

  handlePointerOver(event) {
    const link = event.target.closest(this.selectorValue);
    this.prefetchLink(link);
  }

  handleFocusIn(event) {
    const link = event.target.closest(this.selectorValue);
    this.prefetchLink(link);
  }

  scheduleIdlePrefetch() {
    if (!this.hasIdleSelectorValue) {
      return;
    }

    const run = () => {
      const links = Array.from(this.element.querySelectorAll(this.idleSelectorValue))
        .filter((link) => link instanceof HTMLAnchorElement)
        .slice(0, this.maxIdleValue);

      for (const link of links) {
        this.prefetchLink(link);
      }
    };

    if ("requestIdleCallback" in window) {
      this.idleHandle = window.requestIdleCallback(run, { timeout: 1200 });
    } else {
      this.idleHandle = window.setTimeout(run, 500);
    }
  }

  prefetchLink(link) {
    if (!(link instanceof HTMLAnchorElement)) {
      return;
    }

    if (!this.shouldPrefetch(link)) {
      return;
    }

    const href = link.href;
    if (this.prefetched.has(href)) {
      return;
    }

    if (document.head.querySelector(`link[rel="prefetch"][href="${CSS.escape(href)}"]`)) {
      this.prefetched.add(href);
      return;
    }

    const tag = document.createElement("link");
    tag.rel = "prefetch";
    tag.as = "document";
    tag.href = href;
    document.head.appendChild(tag);
    this.prefetched.add(href);
  }

  shouldPrefetch(link) {
    if (!link.href) {
      return false;
    }

    if (link.target && link.target !== "_self") {
      return false;
    }

    if (link.hasAttribute("download")) {
      return false;
    }

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) {
      return false;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search) {
      return false;
    }

    if (url.hash && url.pathname === window.location.pathname) {
      return false;
    }

    return true;
  }
}

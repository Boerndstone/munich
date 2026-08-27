import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["content", "status"];

  static values = {
    areaSlug: String,
    endpoint: String,
    loaded: { type: Boolean, default: false },
  };

  connect() {
    if (this.element.hasAttribute("open")) {
      this.loadIfNeeded();
    }
  }

  toggle() {
    if (this.element.hasAttribute("open")) {
      this.loadIfNeeded();
    }
  }

  async loadIfNeeded() {
    if (this.loadedValue || !this.endpointValue) {
      return;
    }

    this.loadedValue = true;
    if (this.hasStatusTarget) {
      this.statusTarget.textContent = "Loading...";
      this.statusTarget.hidden = false;
    }

    try {
      const response = await fetch(this.endpointValue, {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`Failed to load navigation for ${this.areaSlugValue}`);
      }

      const payload = await response.json();
      if (this.hasContentTarget) {
        this.contentTarget.innerHTML = payload.html || "";
      }
      if (this.hasStatusTarget) {
        this.statusTarget.hidden = true;
        this.statusTarget.textContent = "";
      }
    } catch (error) {
      this.loadedValue = false;
      if (this.hasStatusTarget) {
        this.statusTarget.textContent = "Could not load crags.";
        this.statusTarget.hidden = false;
      }
    }
  }
}

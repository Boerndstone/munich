import { Controller } from "@hotwired/stimulus";

/** Shadcn-style tab panels (Symfony UX Toolkit). Used e.g. in the search modal. */
export default class extends Controller {
  static targets = ["trigger", "tab"];
  static values = { activeTab: String };

  open(e) {
    this.activeTabValue = e.currentTarget.dataset.tabId;
  }

  activeTabValueChanged() {
    this.triggerTargets.forEach((trigger) => {
      const isActive = trigger.dataset.tabId === this.activeTabValue;
      trigger.dataset.state = isActive ? "active" : "inactive";
      trigger.ariaSelected = isActive;
    });

    this.tabTargets.forEach((tab) => {
      tab.dataset.state = tab.dataset.tabId === this.activeTabValue ? "active" : "inactive";
    });

    if (this.element.closest("#searchModal")) {
      document.dispatchEvent(
        new CustomEvent("search-modal:tab-changed", {
          bubbles: true,
          detail: { value: this.activeTabValue },
        })
      );
    }
  }
}

import { Controller } from "stimulus";

/**
 * Keeps only one <details> among direct children open (accordion behaviour).
 * Binds `toggle` on each <details> (toggle may not bubble). Also defers on
 * summary `click` capture so `open` is correct after the UA default action.
 */
export default class extends Controller {
  connect() {
    this.handlers = [];
    this.boundSummaryClick = this.onSummaryClick.bind(this);

    this.element.querySelectorAll(":scope > details").forEach((details) => {
      const onToggle = () => {
        if (details.open) {
          this.closeOtherDetails(details);
        }
      };
      details.addEventListener("toggle", onToggle);
      this.handlers.push({ details, onToggle });
    });

    this.element.addEventListener("click", this.boundSummaryClick, true);
  }

  disconnect() {
    this.handlers.forEach(({ details, onToggle }) => {
      details.removeEventListener("toggle", onToggle);
    });
    this.handlers = [];
    this.element.removeEventListener("click", this.boundSummaryClick, true);
  }

  onSummaryClick(event) {
    const el = event.target;
    if (!(el instanceof Element)) {
      return;
    }
    const summary = el.closest("summary");
    if (!summary || !this.element.contains(summary)) {
      return;
    }
    const details = summary.parentElement;
    if (!(details instanceof HTMLDetailsElement) || details.parentElement !== this.element) {
      return;
    }

    window.setTimeout(() => {
      if (details.open) {
        this.closeOtherDetails(details);
      }
    }, 0);
  }

  closeOtherDetails(keepOpen) {
    this.element.querySelectorAll(":scope > details").forEach((el) => {
      if (el !== keepOpen) {
        el.removeAttribute("open");
      }
    });
  }
}

import { Controller } from "stimulus";

export default class extends Controller {
  static values = {
    dismissAfter: { type: Number, default: 2 },
  };

  connect() {
    this.timeoutId = window.setTimeout(() => this.dismiss(), this.dismissAfterValue * 1000);
  }

  disconnect() {
    if (this.timeoutId) {
      window.clearTimeout(this.timeoutId);
    }
  }

  dismiss() {
    this.element.remove();
  }
}

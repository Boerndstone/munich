import { Controller } from "stimulus";
import {
  getGradeDisplayMode,
  setGradeDisplayMode,
} from "../js/climbing_grade_display";

export default class extends Controller {
  static targets = ["toggle", "peerLabel"];

  static values = {
    uiaaPeer: { type: String, default: "UIAA" },
    frenchPeer: { type: String, default: "FR" },
  };

  connect() {
    this._onGradeDisplay = () => this.applyFromStorage();
    document.addEventListener("munich:grade-display", this._onGradeDisplay);
    this.applyFromStorage();
  }

  disconnect() {
    document.removeEventListener("munich:grade-display", this._onGradeDisplay);
  }

  applyFromStorage() {
    const french = getGradeDisplayMode() === "french";
    if (this.hasToggleTarget) {
      this.toggleTarget.checked = french;
      this.toggleTarget.setAttribute("aria-checked", french ? "true" : "false");
    }
    this.updatePeerLabel(french);
  }

  /** @param {boolean} french */
  updatePeerLabel(french) {
    if (!this.hasPeerLabelTarget) return;
    this.peerLabelTarget.textContent = french
      ? this.uiaaPeerValue
      : this.frenchPeerValue;
    this.peerLabelTarget.classList.remove(
      "justify-end",
      "pe-1.5",
      "justify-start",
      "ps-1.5",
    );
    if (french) {
      this.peerLabelTarget.classList.add("justify-start", "ps-1.5");
    } else {
      this.peerLabelTarget.classList.add("justify-end", "pe-1.5");
    }
  }

  toggleChanged() {
    const french = this.toggleTarget.checked;
    setGradeDisplayMode(french ? "french" : "uiaa");
  }
}

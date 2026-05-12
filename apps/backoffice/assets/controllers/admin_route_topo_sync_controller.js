import { Controller } from "@hotwired/stimulus";

/**
 * Loads topo name choices for the selected rock (Routes form). Server-side PRE_SUBMIT
 * cannot run until save; this keeps the topo <select> in sync when the rock changes.
 */
export default class extends Controller {
  static targets = ["rock", "topo"];
  static values = { urlTemplate: String };

  connect() {
    if (!this.hasRockTarget || !this.hasTopoTarget) {
      return;
    }
    this._onRockChange = () => {
      void this.refreshTopoOptions();
    };
    this.rockTarget.addEventListener("change", this._onRockChange);
    // EasyAdmin Fels autocomplete uses Tom Select on the same <select>; hook its change too
    this._eaAutocompleteConnect = (event) => {
      if (event.target !== this.rockTarget) {
        return;
      }
      const tomSelect = event.detail?.tomSelect;
      if (tomSelect && typeof tomSelect.on === "function") {
        tomSelect.on("change", this._onRockChange);
      }
    };
    this.element.addEventListener(
      "ea.autocomplete.connect",
      this._eaAutocompleteConnect,
    );
    void this.refreshTopoOptions();
  }

  disconnect() {
    this.element.removeEventListener(
      "ea.autocomplete.connect",
      this._eaAutocompleteConnect,
    );
    if (this.hasRockTarget && this._onRockChange) {
      this.rockTarget.removeEventListener("change", this._onRockChange);
    }
  }

  async refreshTopoOptions() {
    const rockId = this.rockTarget.value;
    const previous = this.topoTarget.value;

    if (!rockId) {
      this.replaceTopoOptions([], previous);
      return;
    }

    const url = this.urlTemplateValue.replace("__ROCK_ID__", rockId);
    try {
      const res = await fetch(url, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });
      if (!res.ok) {
        return;
      }
      const data = await res.json();
      const topos = Array.isArray(data.topos) ? data.topos : [];
      this.replaceTopoOptions(topos, previous);
    } catch {
      // keep existing options if request fails
    }
  }

  replaceTopoOptions(items, previousValue) {
    const sel = this.topoTarget;
    sel.innerHTML = "";
    const empty = document.createElement("option");
    empty.value = "";
    empty.textContent = "Topo wählen …";
    sel.appendChild(empty);

    let matched = false;
    for (const item of items) {
      if (item == null || item.value === undefined) {
        continue;
      }
      const opt = document.createElement("option");
      opt.value = String(item.value);
      opt.textContent = item.label != null ? String(item.label) : opt.value;
      if (String(item.value) === String(previousValue)) {
        matched = true;
      }
      sel.appendChild(opt);
    }
    sel.value = matched && previousValue !== "" ? String(previousValue) : "";
  }
}

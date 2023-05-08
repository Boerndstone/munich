import { Controller } from "stimulus";
//useDebounce does not work
import { useClickOutside } from "stimulus-use";
//import { useClickOutside, useDebounce } from "stimulus-use";
export default class extends Controller {
  static values = {
    url: String,
  };
  static targets = ["result"];
  // search bounce does not work properly
  //static debounces = ["search"];
  connect() {
    useClickOutside(this);
    //useDebounce(this);
  }
  onSearchInput(event) {
    this.search(event.currentTarget.value);
  }

  async search(query) {
    const params = new URLSearchParams({
      q: query,
      preview: 1,
    });
    const response = await fetch(`${this.urlValue}?${params.toString()}`);
    this.resultTarget.innerHTML = await response.text();
  }
  clickOutside(event) {
    this.resultTarget.innerHTML = "";
  }
}

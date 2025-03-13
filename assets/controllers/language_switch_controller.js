import { Controller } from "stimulus";

export default class extends Controller {
  static targets = ["switch"];

  connect() {
    this.switchTarget.addEventListener(
      "change",
      this.switchLanguage.bind(this)
    );
  }

  disconnect() {
    this.switchTarget.removeEventListener(
      "change",
      this.switchLanguage.bind(this)
    );
  }

  switchLanguage(event) {
    const currentLocale = event.target.checked ? "en" : "de";
    const areaSlug = this.switchTarget.dataset.areaSlug;
    const rockSlug = this.switchTarget.dataset.rockSlug;
    const routeName = currentLocale === "en" ? "show_rock_en" : "show_rock";
    const url = new URL(window.location.origin + window.location.pathname);
    url.pathname = Routing.generate(routeName, {
      areaSlug: areaSlug,
      slug: rockSlug,
    });
    window.location.href = url.toString();
  }
}

import ScrollTo from "stimulus-scroll-to";

export default class extends ScrollTo {
  static targets = ["top"];

  scrollFunction() {
    const scrolled =
      document.body.scrollTop > 20 ||
      document.documentElement.scrollTop > 20;
    if (scrolled) {
      this.topTarget.classList.remove("hidden");
      this.topTarget.classList.add("inline-flex");
    } else {
      this.topTarget.classList.add("hidden");
      this.topTarget.classList.remove("inline-flex");
    }
  }
}

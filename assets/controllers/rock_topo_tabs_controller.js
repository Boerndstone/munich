import { Controller } from "@hotwired/stimulus";

/** Rock page topo anchor tabs (scroll + highlight). Not the Shadcn `tabs` controller. */
export default class extends Controller {
  connect() {
    this._tabsList = this.element.querySelector("ul");
    if (!this._tabsList) {
      return;
    }

    this._tabs = this._tabsList.querySelectorAll("a");
    const header =
      document.querySelector("body > header") || document.querySelector(".navbar");
    this._navigationHeight = (header?.offsetHeight ?? 50) + 41;

    this._onTabClick = this._onTabClick.bind(this);
    this._onWindowScroll = this._onWindowScroll.bind(this);

    this._tabsList.addEventListener("click", this._onTabClick);
    window.addEventListener("scroll", this._onWindowScroll, { passive: true });
  }

  disconnect() {
    if (this._tabsList && this._onTabClick) {
      this._tabsList.removeEventListener("click", this._onTabClick);
    }
    if (this._onWindowScroll) {
      window.removeEventListener("scroll", this._onWindowScroll);
    }
    this._tabsList = null;
    this._tabs = null;
    this._onTabClick = null;
    this._onWindowScroll = null;
  }

  _removeAllActiveClasses() {
    this._tabs?.forEach((tab) => {
      tab.classList.remove("active");
    });
  }

  _centerTab(tab) {
    if (!this._tabsList) return;
    const tabRect = tab.getBoundingClientRect();
    const containerRect = this._tabsList.getBoundingClientRect();
    const offset =
      tabRect.left -
      containerRect.left -
      containerRect.width / 2 +
      tabRect.width / 2;

    this._tabsList.scrollBy({
      left: offset,
      behavior: "smooth",
    });
  }

  _onTabClick(event) {
    const tab = event.target.closest("a");
    if (!tab || !this._tabsList.contains(tab)) {
      return;
    }

    event.preventDefault();
    this._removeAllActiveClasses();
    tab.classList.add("active");
    this._centerTab(tab);

    const targetId = tab.getAttribute("href")?.slice(1);
    if (!targetId) return;
    const targetCard = document.getElementById(targetId);
    if (targetCard) {
      const cardOffset = targetCard.offsetTop - this._navigationHeight;
      window.scrollTo({ top: cardOffset, behavior: "smooth" });
    }
  }

  _onWindowScroll() {
    const scrollPos = window.scrollY;

    this._tabs?.forEach((tab) => {
      const targetId = tab.getAttribute("href")?.slice(1);
      if (!targetId) return;
      const targetElement = document.getElementById(targetId);
      if (!targetElement) return;

      const top = targetElement.offsetTop - this._navigationHeight - 200;
      const height = targetElement.offsetHeight;
      if (scrollPos >= top && scrollPos < top + height) {
        this._removeAllActiveClasses();
        tab.classList.add("active");
        this._centerTab(tab);
      }
    });
  }
}

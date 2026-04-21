import { Controller } from "@hotwired/stimulus";

/** Rock page topo anchor tabs (scroll + highlight). Not the Shadcn `tabs` controller. */
export default class extends Controller {
  connect() {
    const tabs = this.element.querySelectorAll("a");
    const tabsList = this.element.querySelector("ul");
    const header =
      document.querySelector("body > header") || document.querySelector(".navbar");

    if (!tabsList) {
      return;
    }

    const navigationHeight = (header?.offsetHeight ?? 50) + 41;

    const removeAllActiveClasses = () => {
      tabs.forEach((tab) => {
        tab.classList.remove("active");
      });
    };

    const centerTab = (tab) => {
      const tabRect = tab.getBoundingClientRect();
      const containerRect = tabsList.getBoundingClientRect();
      const offset =
        tabRect.left -
        containerRect.left -
        containerRect.width / 2 +
        tabRect.width / 2;

      tabsList.scrollBy({
        left: offset,
        behavior: "smooth",
      });
    };

    tabs.forEach((tab) => {
      tab.addEventListener("click", (event) => {
        event.preventDefault();
        removeAllActiveClasses();
        tab.classList.add("active");

        centerTab(tab);

        const targetId = tab.getAttribute("href").slice(1);
        const targetCard = document.getElementById(targetId);
        if (targetCard) {
          const cardOffset = targetCard.offsetTop - navigationHeight;
          window.scrollTo({ top: cardOffset, behavior: "smooth" });
        }
      });
    });

    const onScroll = () => {
      const scrollPos = window.scrollY;

      tabs.forEach((tab) => {
        const targetId = tab.getAttribute("href").slice(1);
        const targetElement = document.getElementById(targetId);
        if (targetElement) {
          const top = targetElement.offsetTop - navigationHeight - 200;
          const height = targetElement.offsetHeight;
          if (scrollPos >= top && scrollPos < top + height) {
            removeAllActiveClasses();

            tab.classList.add("active");

            centerTab(tab);
          }
        }
      });
    };

    window.addEventListener("scroll", onScroll);
  }
}

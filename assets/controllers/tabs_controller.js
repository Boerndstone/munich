import { Controller } from "stimulus";

export default class extends Controller {
  connect() {
    const tabs = document.querySelectorAll(".scrollable-tabs-container a");
    const rightArrow = document.querySelector(
      ".scrollable-tabs-container .right-arrow svg"
    );
    const leftArrow = document.querySelector(
      ".scrollable-tabs-container .left-arrow svg"
    );
    const tabsList = document.querySelector(".scrollable-tabs-container ul");
    const container = document.querySelector(".container");
    const navigationHeight =
      document.querySelector(".navbar").offsetHeight + 41;
    console.log(navigationHeight); // Adjust this selector to match your navigation bar

    let totalWidth = 0;
    tabsList.querySelectorAll("li").forEach((li) => {
      totalWidth += li.offsetWidth;
    });

    const leftArrowContainer = document.querySelector(
      ".scrollable-tabs-container .left-arrow"
    );
    const rightArrowContainer = document.querySelector(
      ".scrollable-tabs-container .right-arrow"
    );

    if (totalWidth < tabsList.clientWidth) {
      rightArrowContainer.classList.remove("active");
    }

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
        event.preventDefault(); // Prevent default anchor behavior
        removeAllActiveClasses();
        tab.classList.add("active");

        // Center the clicked tab
        centerTab(tab);

        // Scroll to the corresponding content with offset
        const targetId = tab.getAttribute("href").slice(1);
        const targetCard = document.getElementById(targetId);
        if (targetCard) {
          const cardOffset = targetCard.offsetTop - navigationHeight; // Adjust for navigation height
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
          const top = targetElement.offsetTop - navigationHeight - 200; // Adjust for navigation height
          const height = targetElement.offsetHeight;
          if (scrollPos >= top && scrollPos < top + height) {
            // Remove active class from all tabs
            removeAllActiveClasses();

            // Add active class to the current tab
            tab.classList.add("active");

            // Center the current tab
            centerTab(tab);
          }
        }
      });
    };

    window.addEventListener("scroll", onScroll);
  }
}

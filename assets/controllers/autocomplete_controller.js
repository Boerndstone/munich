import { Controller } from "stimulus";

export default class extends Controller {
  static targets = ["input", "results"];

  connect() {
    document.addEventListener("click", this.closeDropdown.bind(this));
    this.inputTarget.addEventListener("keydown", this.handleKeydown.bind(this));
  }

  disconnect() {
    document.removeEventListener("click", this.closeDropdown.bind(this));
    this.inputTarget.removeEventListener(
      "keydown",
      this.handleKeydown.bind(this)
    );
  }

  search() {
    const query = this.inputTarget.value.trim();

    fetch(`/search?query=${query}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok " + response.statusText);
        }
        return response.json();
      })
      .then((results) => {
        const { rocks, routes } = results;

        let resultsHtml = "";

        if (rocks.length > 0) {
          resultsHtml += `<li class="list-group-item" style="font-size: 14px;">Felsen</li>`;
          resultsHtml += rocks
            .map(
              (rock) => `
            <li class="list-group-item" data-action="autocomplete#goToResult" data-url="${rock.url}">
              <a class="d-block" style="font-size: 14px;" href="${rock.url}">${rock.name}</a>
            </li>
          `
            )
            .join("");
        }

        if (routes.length > 0) {
          resultsHtml += `<li class="list-group-item" style="font-size: 14px;">Touren</li>`;
          resultsHtml += routes
            .map(
              (route) => `
            <li class="list-group-item" data-action="autocomplete#goToResult" data-url="${route.url}">
              <a class="d-block" style="font-size: 14px;" href="${route.url}">${route.area} | ${route.rock} | Route: ${route.name}  </a>
            </li>
          `
            )
            .join("");
        }

        if (resultsHtml === "") {
          resultsHtml = `<li class="list-group-item text-danger">Keine Ergebnisse!</li>`;
        }

        this.resultsTarget.innerHTML = resultsHtml;
      })
      .catch((error) => {
        this.resultsTarget.innerHTML = `<li class="list-group-item text-danger">Error: ${error.message}</li>`;
      });
  }

  handleKeydown(event) {
    const items = this.resultsTarget.querySelectorAll(
      ".list-group-item[data-url]"
    );
    let index = Array.from(items).findIndex((item) =>
      item.classList.contains("active-item")
    );

    if (event.key === "ArrowDown") {
      event.preventDefault();
      if (index < items.length - 1) {
        index++;
      } else {
        index = 0; // Wrap around to the first item
      }
    } else if (event.key === "ArrowUp") {
      event.preventDefault();
      if (index > 0) {
        index--;
      } else {
        index = items.length - 1; // Wrap around to the last item
      }
    } else if (event.key === "Enter") {
      event.preventDefault();
      if (index >= 0) {
        const activeItem = items[index];
        window.location.href = activeItem.dataset.url;
      }
    }

    items.forEach((item) => item.classList.remove("active-item"));
    if (index >= 0) {
      items[index].classList.add("active-item");
      items[index].scrollIntoView({ block: "nearest" });
    }
  }

  closeDropdown(event) {
    if (!this.element.contains(event.target)) {
      this.resultsTarget.innerHTML = "";
      this.inputTarget.value = "";
    }
  }

  goToResult(event) {
    window.location.href = event.target.dataset.url;
  }
}

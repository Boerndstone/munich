import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["input", "results", "searchMode"];

  connect() {
    document.addEventListener("click", this.closeDropdown.bind(this));
    this.inputTarget.addEventListener("keydown", this.handleKeydown.bind(this));
    
    // Add search mode listener if the target exists
    if (this.hasSearchModeTarget) {
      this.searchModeTarget.addEventListener("change", this.onSearchModeChange.bind(this));
    }
    
    // Prevent body scroll when dropdown is open
    this.originalOverflow = document.body.style.overflow;
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
    const searchMode = this.hasSearchModeTarget ? this.searchModeTarget.value : 'name';

    // Update placeholder based on search mode
    if (this.hasSearchModeTarget) {
      this.updatePlaceholder(searchMode);
    }

    let url = `/search?query=${query}&mode=${searchMode}`;

    fetch(url)
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok " + response.statusText);
        }
        return response.json();
      })
      .then((results) => {
        const { rocks, routes, searchMode } = results;

        let resultsHtml = "";

        // Show different results based on search mode
        if (searchMode === 'firstascent') {
          // For first ascent search, show routes with first ascent info
          if (routes.length > 0) {
            resultsHtml += `<li class="list-group-item" style="font-size: 14px;">Erstbegeher: ${this.highlightText(query, query)}</li>`;
            resultsHtml += routes
              .map(
                (route) => {
                  const routeAnchor = route.name ? `#${route.name.replace(/\s+/g, '').toLowerCase()}` : '';
                  const fullUrl = route.url + routeAnchor;
                  return `
              <li class="list-group-item" data-action="click->autocomplete#goToResult" data-url="${route.url}" data-route-name="${route.name || ''}">
                <a class="d-block" style="font-size: 14px; cursor: pointer;" href="${fullUrl}">${this.highlightText(route.name, query)} (${this.highlightText(route.firstAscent, query)}) - ${this.highlightText(route.area, query)} | ${this.highlightText(route.rock, query)}</a>
              </li>
            `;
                }
              )
              .join("");
          }
        } else {
          // Default name search - show both rocks and routes
          if (rocks.length > 0) {
            resultsHtml += `<li class="list-group-item" style="font-size: 14px; font-weight: bold;">Felsen</li>`;
            resultsHtml += rocks
              .map(
                (rock) => `
              <li class="list-group-item" data-action="click->autocomplete#goToResult" data-url="${rock.url}">
                <a class="d-block" style="font-size: 14px; cursor: pointer;">${this.highlightText(rock.name, query)}</a>
              </li>
            `
              )
              .join("");
          }

          if (routes.length > 0) {
            resultsHtml += `<li class="list-group-item" style="font-size: 14px; font-weight: bold;">Touren</li>`;
            resultsHtml += routes
              .map(
                (route) => {
                  const routeAnchor = route.name ? `#${route.name.replace(/\s+/g, '').toLowerCase()}` : '';
                  const fullUrl = route.url + routeAnchor;
                  return `
              <li class="list-group-item" data-action="click->autocomplete#goToResult" data-url="${route.url}" data-route-name="${route.name || ''}">
                <a class="d-block" style="font-size: 14px; cursor: pointer;" href="${fullUrl}">${this.highlightText(route.area, query)} | ${this.highlightText(route.rock, query)} | Route: ${this.highlightText(route.name, query)}</a>
              </li>
            `;
                }
              )
              .join("");
          }
        }

        if (resultsHtml === "") {
          resultsHtml = `<li class="list-group-item text-danger">Keine Ergebnisse!</li>`;
        }

        this.resultsTarget.innerHTML = resultsHtml;
        
        // If we have results, prevent body scroll and make results scrollable
        if (resultsHtml !== "" && resultsHtml !== `<li class="list-group-item text-danger">Keine Ergebnisse!</li>`) {
          document.body.style.overflow = 'hidden';
          this.resultsTarget.style.maxHeight = '500px';
          this.resultsTarget.style.overflowY = 'auto';
        }
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
        const url = this.buildUrl(activeItem);
        window.location.href = url;
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
      // Restore body scroll
      document.body.style.overflow = this.originalOverflow;
    }
  }

  goToResult(event) {
    event.preventDefault();
    // Get the <li> element that has the data-url attribute
    const listItem = event.currentTarget || event.target.closest('li[data-url]');
    if (listItem) {
      const url = this.buildUrl(listItem);
      window.location.href = url;
    }
  }

  buildUrl(listItem) {
    let url = listItem.dataset.url;
    // If it's a route (has data-route-name), append the route anchor
    if (listItem.dataset.routeName) {
      const routeAnchor = `#${listItem.dataset.routeName.replace(/\s+/g, '').toLowerCase()}`;
      url = url + routeAnchor;
    }
    return url;
  }

  highlightText(text, query) {
    if (!query || query.length < 2) return text;
    
    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<strong>$1</strong>');
  }

  updatePlaceholder(mode) {
    const placeholders = {
      'name': 'Suche nach Felsen oder Routen...',
      'firstascent': 'Suche nach Erstbegeher...'
    };
    
    this.inputTarget.placeholder = placeholders[mode] || 'Suche...';
  }

  onSearchModeChange() {
    const mode = this.searchModeTarget.value;
    this.updatePlaceholder(mode);
    this.clearResults();
  }

  clearResults() {
    this.resultsTarget.innerHTML = '';
    // Restore body scroll when clearing results
    document.body.style.overflow = this.originalOverflow;
  }

}

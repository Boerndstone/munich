import { Controller } from "@hotwired/stimulus";
import { Modal } from "bootstrap";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = { searchUrl: { type: String, default: '/search' } };
  static targets = [
    "trigger", "modal", "nameInput", "firstAscentInput", "areaSelect",
    "areaSelectAttributes", "gradeCheck", "attrChildFriendly", "attrSunny", "attrRainProtected",
    "resultsContainer", "resultsCount", "rocksSection", "rocksList",
    "routesSection", "routesTable", "emptyState", "idleState"
  ];

  connect() {
    this.bootstrapModal = null;
    this._debounceTimers = {};
    const tabsEl = document.getElementById('searchTabs');
    if (tabsEl) {
      tabsEl.addEventListener('shown.bs.tab', () => this.clearResults());
    }
  }

  disconnect() {
    if (this.bootstrapModal) {
      this.bootstrapModal.dispose();
    }
  }

  open(event) {
    event?.preventDefault();
    if (!this.hasModalTarget) return;
    if (!this.bootstrapModal) {
      this.bootstrapModal = new Modal(this.modalTarget);
    }
    this.bootstrapModal.show();
    setTimeout(() => this.modalTarget.querySelector('input[type="search"]')?.focus(), 300);
  }

  searchNameDebounced() {
    this.debounce('name', () => this.searchName(), 300);
  }

  searchFirstAscentDebounced() {
    this.debounce('firstascent', () => this.searchFirstAscent(), 300);
  }

  debounce(key, fn, ms) {
    if (this._debounceTimers[key]) clearTimeout(this._debounceTimers[key]);
    this._debounceTimers[key] = setTimeout(() => {
      fn();
      delete this._debounceTimers[key];
    }, ms);
  }

  async searchName() {
    const query = this.nameInputTarget.value.trim();
    if (query.length < 2) {
      this.clearResults();
      return;
    }
    await this.fetchResults('name', { query });
  }

  async searchFirstAscent() {
    const query = this.firstAscentInputTarget.value.trim();
    if (query.length < 2) {
      this.clearResults();
      return;
    }
    await this.fetchResults('firstascent', { query });
  }

  clearResults() {
    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.add('d-none');
    if (this.hasIdleStateTarget) this.idleStateTarget.classList.remove('d-none');
    if (this.hasResultsCountTarget) this.resultsCountTarget.textContent = '0';
    if (this.hasRocksListTarget) this.rocksListTarget.innerHTML = '';
    if (this.hasRoutesTableTarget) this.routesTableTarget.innerHTML = '';
    if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add('d-none');
    if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add('d-none');
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add('d-none');
      this.emptyStateTarget.textContent = 'Keine Ergebnisse gefunden.';
    }
  }

  async searchByGrade() {
    const grades = this.gradeCheckTargets.filter(cb => cb.checked).map(cb => cb.value);
    if (grades.length === 0) return;
    const area = this.areaSelectTarget?.value || '';
    await this.fetchResults('grade', { grades, area });
  }

  async searchByAttributes() {
    const childFriendly = this.hasAttrChildFriendlyTarget && this.attrChildFriendlyTarget.checked;
    const sunny = this.hasAttrSunnyTarget && this.attrSunnyTarget.checked;
    const rainProtected = this.hasAttrRainProtectedTarget && this.attrRainProtectedTarget.checked;
    if (!childFriendly && !sunny && !rainProtected) return;
    const area = this.hasAreaSelectAttributesTarget ? this.areaSelectAttributesTarget.value : '';
    await this.fetchResults('attributes', {
      childFriendly,
      sunny,
      rainProtected,
      area,
    });
  }

  async fetchResults(mode, params) {
    const baseUrl = this.hasSearchUrlValue ? this.searchUrlValue : '/search';
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set('mode', mode);
    if (params.query) url.searchParams.set('query', params.query);
    if (params.area) url.searchParams.set('area', params.area);
    if (params.grades?.length) {
      params.grades.forEach(g => url.searchParams.append('grades[]', g));
    }
    if (params.childFriendly) url.searchParams.set('childFriendly', '1');
    if (params.sunny) url.searchParams.set('sunny', '1');
    if (params.rainProtected) url.searchParams.set('rainProtected', '1');

    this.showLoading();
    try {
      const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
      const contentType = res.headers.get('Content-Type') || '';
      const data = contentType.includes('application/json') ? await res.json() : null;
      if (!res.ok) {
        const msg = data?._error || `Anfrage fehlgeschlagen (${res.status})`;
        throw new Error(msg);
      }
      if (!data) {
        throw new Error('Unerwartete Antwort vom Server.');
      }
      this.renderResults(data);
    } catch (err) {
      this.renderError(err.message);
    }
  }

  showLoading() {
    if (this.hasIdleStateTarget) this.idleStateTarget.classList.add('d-none');
    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.remove('d-none');
    if (this.hasEmptyStateTarget) this.emptyStateTarget.classList.add('d-none');
    if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add('d-none');
    if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add('d-none');
    if (this.hasRoutesTableTarget) this.routesTableTarget.innerHTML = '<div class="text-center py-3">Lade...</div>';
  }

  renderResults(data) {
    const { rocks = [], routes = [], searchMode } = data;
    const total = rocks.length + routes.length;

    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.remove('d-none');
    if (this.hasIdleStateTarget) this.idleStateTarget.classList.add('d-none');
    if (this.hasResultsCountTarget) this.resultsCountTarget.textContent = total;

    if (total === 0) {
      if (this.hasEmptyStateTarget) this.emptyStateTarget.classList.remove('d-none');
      if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add('d-none');
      if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add('d-none');
      return;
    }

    if (this.hasEmptyStateTarget) this.emptyStateTarget.classList.add('d-none');

    // Rocks (for name search and attributes search)
    if ((searchMode === 'name' || searchMode === 'attributes') && rocks.length > 0 && this.hasRocksSectionTarget && this.hasRocksListTarget) {
      this.rocksSectionTarget.classList.remove('d-none');
      this.rocksListTarget.innerHTML = rocks.map(r => `
        <li class="list-group-item list-group-item-action p-1">
          <a href="${r.url}" class="text-decoration-none">${this.escapeHtml(r.name)}</a>
        </li>
      `).join('');
    } else if (this.hasRocksSectionTarget) {
      this.rocksSectionTarget.classList.add('d-none');
    }

    // Routes table
    if (routes.length > 0 && this.hasRoutesSectionTarget && this.hasRoutesTableTarget) {
      this.routesSectionTarget.classList.remove('d-none');
      this.routesTableTarget.innerHTML = routes.map(r => {
        const routeAnchor = r.name ? `#${String(r.name).replace(/\s+/g, '').toLowerCase()}` : '';
        const fullUrl = r.url + routeAnchor;
        return `
        <li class="list-group-item list-group-item-action px-0 w-full" style="border-bottom: 1px solid #dee2e6;">
          <a href="${fullUrl}" class="text-decoration-none d-flex align-items-center justify-content-between row p-1">
            <div class="col-5 small d-flex align-items-center text-truncate">${this.escapeHtml(r.name)}</div>
            <div class="col-1 small ">${r.grade ? `${this.escapeHtml(r.grade)}` : ''}</div>
            <div class="col-3 small text-truncate">${this.escapeHtml(r.rock)}</div>
            <div class="col-3 small text-truncate">${this.escapeHtml(r.area)}</div>
          </a>
        </li>
        `;
      }).join('');
    } else if (this.hasRoutesSectionTarget) {
      this.routesSectionTarget.classList.add('d-none');
    }
  }

  renderError(msg) {
    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.remove('d-none');
    if (this.hasResultsCountTarget) this.resultsCountTarget.textContent = '0';
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.remove('d-none');
      this.emptyStateTarget.textContent = `Fehler: ${msg}`;
    }
    if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add('d-none');
    if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add('d-none');
  }

  escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

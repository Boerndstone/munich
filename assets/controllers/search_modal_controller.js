import { Controller } from "stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = { searchUrl: { type: String, default: '/search' } };
  static targets = [
    "modal", "nameInput", "firstAscentInput", "areaSelect",
    "areaSelectAttributes", "gradeCheck", "attrChildFriendly", "attrSunny", "attrRainProtected", "attrTrain", "attrBike",
    "resultsContainer", "resultsCount", "rocksSection", "rocksList",
    "routesSection", "routesTable", "emptyState", "idleState",
    "pagerContainer", "pagerPrev", "pagerNext", "pagerInfo"
  ];

  connect() {
    this._debounceTimers = {};
    this._gradePagination = null; // { grades, area, totalCount, page, perPage }
    this._scrollLockActive = false;
    this._onToggle = () => {
      if (!this.hasModalTarget || !this.modalTarget.open) return;
      const activeBtn = this.modalTarget.querySelector('[role="tab"][aria-selected="true"]');
      if (activeBtn) this.scrollActiveTabToCenter(activeBtn);
    };
    this._onModalClose = () => {
      this.unlockBodyScroll();
    };
    this._onSearchModalTabChanged = () => {
      this.clearResults();
      if (!this.hasModalTarget) return;
      const activeBtn = this.modalTarget.querySelector('[role="tab"][aria-selected="true"]');
      if (activeBtn) this.scrollActiveTabToCenter(activeBtn);
    };
    if (this.hasModalTarget) {
      this.modalTarget.addEventListener("toggle", this._onToggle);
      this.modalTarget.addEventListener("close", this._onModalClose);
    }
    document.addEventListener("search-modal:tab-changed", this._onSearchModalTabChanged);
  }

  disconnect() {
    document.removeEventListener("search-modal:tab-changed", this._onSearchModalTabChanged);
    if (this.hasModalTarget && this._onToggle) {
      this.modalTarget.removeEventListener("toggle", this._onToggle);
    }
    if (this.hasModalTarget && this._onModalClose) {
      this.modalTarget.removeEventListener("close", this._onModalClose);
    }
    this.unlockBodyScroll();
  }

  scrollActiveTabToCenter(activeButton) {
    const tabsEl = document.getElementById("searchTabs");
    if (!tabsEl || !activeButton) return;
    const btn = activeButton;
    const scrollLeft = btn.offsetLeft - (tabsEl.offsetWidth / 2) + (btn.offsetWidth / 2);
    tabsEl.scrollTo({ left: Math.max(0, scrollLeft), behavior: "smooth" });
  }

  open(event) {
    event?.preventDefault();
    if (!this.hasModalTarget) return;
    this.modalTarget.showModal();
    this.lockBodyScroll();
    requestAnimationFrame(() => {
      const input = this.modalTarget.querySelector(
        '[role="tabpanel"][data-state="active"] input[type="search"]'
      );
      input?.focus();
    });
  }

  /** Prevent scrolling the page behind the top-layer dialog (backdrop does not block wheel on all engines). */
  lockBodyScroll() {
    if (this._scrollLockActive) return;
    this._scrollLockActive = true;
    const gutter = window.innerWidth - document.documentElement.clientWidth;
    if (gutter > 0) {
      document.body.style.paddingRight = `${gutter}px`;
    }
    document.documentElement.style.overflow = "hidden";
    document.body.style.overflow = "hidden";
  }

  unlockBodyScroll() {
    if (!this._scrollLockActive) return;
    this._scrollLockActive = false;
    document.documentElement.style.overflow = "";
    document.body.style.overflow = "";
    document.body.style.paddingRight = "";
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
    this._gradePagination = null;
    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.add("hidden");
    if (this.hasIdleStateTarget) this.idleStateTarget.classList.remove("hidden");
    if (this.hasResultsCountTarget) this.resultsCountTarget.textContent = "0";
    if (this.hasRocksListTarget) this.rocksListTarget.innerHTML = "";
    if (this.hasRoutesTableTarget) this.routesTableTarget.innerHTML = "";
    if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add("hidden");
    if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add("hidden");
    if (this.hasPagerContainerTarget) this.pagerContainerTarget.classList.add("hidden");
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add("hidden");
      this.emptyStateTarget.textContent = "Keine Ergebnisse gefunden.";
    }
  }

  async searchByGrade() {
    const grades = this.gradeCheckTargets.filter(cb => cb.checked).map(cb => cb.value);
    if (grades.length === 0) return;
    const area = this.areaSelectTarget?.value || '';
    await this.fetchResults('grade', { grades, area, page: 1 });
  }

  gradePagePrev() {
    if (!this._gradePagination || this._gradePagination.page <= 1) return;
    const { grades, area, perPage } = this._gradePagination;
    this.fetchResults('grade', { grades, area, page: this._gradePagination.page - 1, perPage });
  }

  gradePageNext() {
    if (!this._gradePagination) return;
    const { totalCount, page, perPage, grades, area } = this._gradePagination;
    if (page * perPage >= totalCount) return;
    this.fetchResults('grade', { grades, area, page: page + 1, perPage });
  }

  async searchByAttributes() {
    const childFriendly = this.hasAttrChildFriendlyTarget && this.attrChildFriendlyTarget.checked;
    const sunny = this.hasAttrSunnyTarget && this.attrSunnyTarget.checked;
    const rainProtected = this.hasAttrRainProtectedTarget && this.attrRainProtectedTarget.checked;
    const train = this.hasAttrTrainTarget && this.attrTrainTarget.checked;
    const bike = this.hasAttrBikeTarget && this.attrBikeTarget.checked;
    if (!childFriendly && !sunny && !rainProtected && !train && !bike) return;
    const area = this.hasAreaSelectAttributesTarget ? this.areaSelectAttributesTarget.value : '';
    await this.fetchResults('attributes', {
      childFriendly,
      sunny,
      rainProtected,
      train,
      bike,
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
    if (params.page != null) url.searchParams.set('page', String(params.page));
    if (params.perPage != null) url.searchParams.set('perPage', String(params.perPage));
    if (params.childFriendly) url.searchParams.set('childFriendly', '1');
    if (params.sunny) url.searchParams.set('sunny', '1');
    if (params.rainProtected) url.searchParams.set('rainProtected', '1');
    if (params.train) url.searchParams.set('train', '1');
    if (params.bike) url.searchParams.set('bike', '1');

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
    if (this.hasIdleStateTarget) this.idleStateTarget.classList.add("hidden");
    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.remove("hidden");
    if (this.hasEmptyStateTarget) this.emptyStateTarget.classList.add("hidden");
    if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add("hidden");
    if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add("hidden");
    if (this.hasRoutesTableTarget) {
      this.routesTableTarget.innerHTML = '<div class="py-3 text-center text-sm text-[var(--theme-text)]">Lade…</div>';
    }
  }

  renderResults(data) {
    const { rocks = [], routes = [], routesHtml = '', searchMode, totalCount, page, perPage } = data;
    const total = searchMode === 'grade' && totalCount != null ? totalCount : (rocks.length + routes.length);

    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.remove("hidden");
    if (this.hasIdleStateTarget) this.idleStateTarget.classList.add("hidden");
    if (this.hasResultsCountTarget) this.resultsCountTarget.textContent = total;

    if (searchMode === 'grade' && totalCount != null && page != null && perPage != null) {
      const grades = this.gradeCheckTargets.filter(cb => cb.checked).map(cb => cb.value);
      const area = this.areaSelectTarget?.value || '';
      this._gradePagination = { grades, area, totalCount, page, perPage };
      const totalPages = Math.ceil(totalCount / perPage);
      if (this.hasPagerContainerTarget) {
        this.pagerContainerTarget.classList.remove("hidden");
        if (this.hasPagerInfoTarget) this.pagerInfoTarget.textContent = `Seite ${page} von ${totalPages}`;
        if (this.hasPagerPrevTarget) this.pagerPrevTarget.disabled = page <= 1;
        if (this.hasPagerNextTarget) this.pagerNextTarget.disabled = page >= totalPages;
      }
    } else {
      this._gradePagination = null;
      if (this.hasPagerContainerTarget) this.pagerContainerTarget.classList.add("hidden");
    }

    if (total === 0) {
      if (this.hasEmptyStateTarget) this.emptyStateTarget.classList.remove("hidden");
      if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add("hidden");
      if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add("hidden");
      return;
    }

    if (this.hasEmptyStateTarget) this.emptyStateTarget.classList.add("hidden");

    if ((searchMode === 'name' || searchMode === 'attributes') && rocks.length > 0 && this.hasRocksSectionTarget && this.hasRocksListTarget) {
      this.rocksSectionTarget.classList.remove("hidden");
      this.rocksListTarget.innerHTML = rocks.map(r => `
        <li class="border-b border-[var(--theme-border)] py-2 last:border-b-0">
          <a href="${r.url}" class="text-sm text-[#075985] no-underline hover:underline">${this.escapeHtml(r.name)}</a>
        </li>
      `).join('');
    } else if (this.hasRocksSectionTarget) {
      this.rocksSectionTarget.classList.add("hidden");
    }

    if (routes.length > 0 && this.hasRoutesSectionTarget && this.hasRoutesTableTarget) {
      this.routesSectionTarget.classList.remove("hidden");
      this.routesTableTarget.innerHTML = routesHtml || "";
    } else if (this.hasRoutesSectionTarget) {
      this.routesSectionTarget.classList.add("hidden");
      if (this.hasRoutesTableTarget) this.routesTableTarget.innerHTML = "";
    }
  }

  renderError(msg) {
    if (this.hasResultsContainerTarget) this.resultsContainerTarget.classList.remove("hidden");
    if (this.hasResultsCountTarget) this.resultsCountTarget.textContent = "0";
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.remove("hidden");
      this.emptyStateTarget.textContent = `Fehler: ${msg}`;
    }
    if (this.hasRocksSectionTarget) this.rocksSectionTarget.classList.add("hidden");
    if (this.hasRoutesSectionTarget) this.routesSectionTarget.classList.add("hidden");
    if (this.hasRoutesTableTarget) this.routesTableTarget.innerHTML = "";
  }

  escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

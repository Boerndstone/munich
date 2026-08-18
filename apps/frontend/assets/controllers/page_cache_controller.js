import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    maxEntries: { type: Number, default: 15 },
    ttlMs: { type: Number, default: 30 * 60 * 1000 },
    cacheName: { type: String, default: "munich-pages-v1" },
  };

  connect() {
    this.memory = window.__munichPageCacheStore || new Map();
    window.__munichPageCacheStore = this.memory;
    this.pending = window.__munichPageCachePending || new Map();
    window.__munichPageCachePending = this.pending;

    this.handleClick = this.handleClick.bind(this);
    this.handleTurboLoad = this.handleTurboLoad.bind(this);

    document.addEventListener("click", this.handleClick, true);
    document.addEventListener("turbo:load", this.handleTurboLoad);

    this.exposeApi();
    this.storeCurrentPage();
  }

  disconnect() {
    document.removeEventListener("click", this.handleClick, true);
    document.removeEventListener("turbo:load", this.handleTurboLoad);
  }

  exposeApi() {
    window.MunichPageCache = {
      prime: (url) => this.prime(url),
      has: (url) => this.hasFreshEntry(this.normalizeUrl(url)),
      clear: () => this.clear(),
    };
  }

  handleTurboLoad() {
    this.storeCurrentPage();
  }

  async handleClick(event) {
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return;
    }

    const link = event.target.closest("a[href]");
    if (!(link instanceof HTMLAnchorElement) || !this.shouldHandleLink(link)) {
      return;
    }

    const url = this.normalizeUrl(link.href);
    let entry = this.memory.get(url);

    if (!this.isFreshEntry(entry)) {
      entry = await this.readFromCacheStorage(url);
    }

    if (!this.isFreshEntry(entry)) {
      return;
    }

    event.preventDefault();
    this.touchEntry(url, entry);

    if (window.Turbo?.visit) {
      window.Turbo.visit(url, {
        action: link.dataset.turboAction || "advance",
        response: {
          statusCode: 200,
          redirected: false,
          responseHTML: entry.html,
        },
      });
      return;
    }

    window.location.href = url;
  }

  shouldHandleLink(link) {
    if (!link.href || link.hasAttribute("download") || link.dataset.noPageCache === "true") {
      return false;
    }

    if (link.target && link.target !== "_self") {
      return false;
    }

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) {
      return false;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search) {
      return false;
    }

    return true;
  }

  async prime(url) {
    const normalizedUrl = this.normalizeUrl(url);
    if (this.hasFreshEntry(normalizedUrl)) {
      return this.memory.get(normalizedUrl);
    }

    if (this.pending.has(normalizedUrl)) {
      return this.pending.get(normalizedUrl);
    }

    const request = fetch(normalizedUrl, {
      credentials: "same-origin",
      headers: {
        Accept: "text/html, application/xhtml+xml",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(async (response) => {
        if (!response.ok) {
          return null;
        }

        const html = await response.text();
        const entry = { html, cachedAt: Date.now() };
        this.touchEntry(normalizedUrl, entry);
        await this.writeToCacheStorage(normalizedUrl, entry);
        return entry;
      })
      .catch(() => null)
      .finally(() => {
        this.pending.delete(normalizedUrl);
      });

    this.pending.set(normalizedUrl, request);
    return request;
  }

  storeCurrentPage() {
    const html = document.documentElement.outerHTML;
    const url = this.normalizeUrl(window.location.href);
    const entry = { html, cachedAt: Date.now() };
    this.touchEntry(url, entry);
    void this.writeToCacheStorage(url, entry);
  }

  normalizeUrl(url) {
    const normalized = new URL(url, window.location.href);
    normalized.hash = "";
    return normalized.toString();
  }

  hasFreshEntry(url) {
    return this.isFreshEntry(this.memory.get(url));
  }

  isFreshEntry(entry) {
    return !!entry && Date.now() - entry.cachedAt <= this.ttlMsValue;
  }

  touchEntry(url, entry) {
    if (this.memory.has(url)) {
      this.memory.delete(url);
    }

    this.memory.set(url, entry);

    while (this.memory.size > this.maxEntriesValue) {
      const oldestUrl = this.memory.keys().next().value;
      this.memory.delete(oldestUrl);
      void this.deleteFromCacheStorage(oldestUrl);
    }
  }

  async openCacheStorage() {
    if (!("caches" in window)) {
      return null;
    }

    try {
      return await window.caches.open(this.cacheNameValue);
    } catch {
      return null;
    }
  }

  async writeToCacheStorage(url, entry) {
    const cache = await this.openCacheStorage();
    if (!cache) {
      return;
    }

    const response = new Response(entry.html, {
      headers: {
        "Content-Type": "text/html; charset=utf-8",
        "X-Munich-Cached-At": String(entry.cachedAt),
      },
    });

    await cache.put(url, response);
  }

  async readFromCacheStorage(url) {
    const cache = await this.openCacheStorage();
    if (!cache) {
      return null;
    }

    const response = await cache.match(url);
    if (!response) {
      return null;
    }

    const cachedAt = Number(response.headers.get("X-Munich-Cached-At") || "0");
    const html = await response.text();
    const entry = { html, cachedAt };

    if (!this.isFreshEntry(entry)) {
      await cache.delete(url);
      return null;
    }

    this.touchEntry(url, entry);
    return entry;
  }

  async deleteFromCacheStorage(url) {
    const cache = await this.openCacheStorage();
    if (!cache) {
      return;
    }

    await cache.delete(url);
  }

  async clear() {
    this.memory.clear();
    const cache = await this.openCacheStorage();
    if (cache) {
      await window.caches.delete(this.cacheNameValue);
    }
  }
}

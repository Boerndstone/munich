import { Controller } from "@hotwired/stimulus";
import L from "leaflet";
import "leaflet.markercluster";

/**
 * When rocks exist on the map:
 * - z < 10: each area is a blue “cluster” bubble (count = rocks with coords in that area).
 *   Nearby areas can merge into one bubble; the number is the sum of those rocks (overview).
 * - 10 <= z < 12: same bubbles, but one layer per area only (no merging) so each area stays its own cluster.
 * - z >= 12: rocks only — MarkerClusterGroup splits as you zoom; red pin icons from disableClusteringAtZoom upward.
 */
const Z_AREA_SEPARATE_MIN = 10;
const Z_ROCKS_MIN = 12;

/** Rock clusters: break apart a bit earlier so pins appear sooner inside a crag */
const ROCK_CLUSTER_MAX_RADIUS = 48;
const ROCK_DISABLE_CLUSTERING_AT_ZOOM = 15;

const PIN_SVG =
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 40" width="28" height="40" aria-hidden="true">' +
  '<path fill="#ef4444" stroke="#b91c1c" stroke-width="0.75" d="M14 .5C6.5.5.5 6.4.5 13.9c0 5.2 2.6 10.4 6.5 15.8 3.2 4.4 7 8.3 7 8.3s3.8-3.9 7-8.3c3.9-5.4 6.5-10.6 6.5-15.8C27.5 6.4 21.5.5 14 .5z"/>' +
  '<circle cx="14" cy="14" r="4.25" fill="#fff"/>' +
  "</svg>";

function createMainMapPinIcon() {
  return L.divIcon({
    html: PIN_SVG,
    className: "main-map-pin-icon",
    iconSize: [28, 40],
    iconAnchor: [14, 40],
    popupAnchor: [0, -34],
  });
}

/** Blue glow bubble (shared by area “clusters” and geo rock clusters) */
function bubbleIconForCount(count) {
  const n = Math.max(0, Math.min(9999, Math.round(Number(count) || 0)));
  const d = n < 10 ? 52 : n < 100 ? 60 : n < 1000 ? 64 : 70;
  return L.divIcon({
    html: `<span class="main-map-cluster-count" style="--s:${d}px">${n}</span>`,
    className: "main-map-cluster-icon",
    iconSize: L.point(d, d),
    iconAnchor: L.point(d / 2, d / 2),
  });
}

/** Merged marker cluster: show number of child markers (rocks only) */
function createRockGeoClusterIcon(cluster) {
  return bubbleIconForCount(cluster.getChildCount());
}

/** Merged area markers: show total rocks-with-coords across grouped areas */
function createMergedAreaRockCountIcon(cluster) {
  const markers = cluster.getAllChildMarkers();
  const total = markers.reduce((s, mm) => s + (Number(mm.options.rocksWithCoordinates) || 0), 0);
  return bubbleIconForCount(total);
}

function addAreaBubbleMarkers(areaData, targetLayer) {
  areaData.forEach((m) => {
    const lat = _getCoordStatic(m, 0);
    const lng = _getCoordStatic(m, 1);
    if (lat == null || lng == null || Number.isNaN(lat) || Number.isNaN(lng)) return;
    const popup = Array.isArray(m) ? (m[2] ?? "") : (m.popup ?? "");
    const rc = Array.isArray(m) ? (m[5] ?? 0) : (m.rocksWithCoordinates ?? 0);
    const rockN = Number(rc) || 0;
    const marker = L.marker([lat, lng], {
      icon: bubbleIconForCount(rockN),
      rocksWithCoordinates: rockN,
    }).bindPopup(popup);
    targetLayer.addLayer(marker);
  });
}

function _getCoordStatic(m, index) {
  if (Array.isArray(m)) {
    const v = m[index];
    return v != null ? parseFloat(v) : NaN;
  }
  const key = index === 0 ? "lat" : "lng";
  const v = m[key];
  return v != null ? parseFloat(v) : NaN;
}

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["map", "filters", "filterBtn"];
  static values = {
    markers: Array,
    rockMarkers: { type: Array, default: [] },
  };

  connect() {
    this.map = L.map(this.mapTarget).setView([48.74, 12.44], 7);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18,
    }).addTo(this.map);

    this.areaClusterGroup = null;
    this.areaSummaryLayer = null;
    this.rockClusterGroup = null;
    this.currentRange = "all";

    this._zoomHandler = () => this.syncClusterLayerVisibility();
    this.map.on("zoomend", this._zoomHandler);

    this.rebuildClusterLayers();

    if (this.hasFilterBtnTarget) {
      this.filterBtnTargets.forEach((btn) => {
        btn.addEventListener("click", (e) => this.applyFilter(e));
      });
    }

    const dialog = this.element.closest("dialog");
    if (dialog) {
      this._dialogOpenObserver = new MutationObserver(() => {
        if (dialog.open) {
          setTimeout(() => this.map.invalidateSize(), 40);
        }
      });
      this._dialogOpenObserver.observe(dialog, {
        attributes: true,
        attributeFilter: ["open"],
      });
    }
  }

  disconnect() {
    this.map.off("zoomend", this._zoomHandler);
    this._removeClusterGroups();
    this._dialogOpenObserver?.disconnect();
  }

  _removeClusterGroups() {
    [this.areaClusterGroup, this.areaSummaryLayer, this.rockClusterGroup].forEach((g) => {
      if (g && this.map.hasLayer(g)) {
        this.map.removeLayer(g);
      }
    });
    this.areaClusterGroup = null;
    this.areaSummaryLayer = null;
    this.rockClusterGroup = null;
  }

  getFilteredMarkers() {
    const markers = this.markersValue || [];
    return this._filterByTravelRange(markers);
  }

  getFilteredRockMarkers() {
    const rocks = this.rockMarkersValue || [];
    return this._filterByTravelRange(rocks);
  }

  _filterByTravelRange(markers) {
    const rangeRaw = (this.currentRange ?? "").toString().trim();
    if (!rangeRaw || rangeRaw === "all") {
      return markers;
    }

    let min = -Infinity;
    let max = Infinity;

    if (rangeRaw.endsWith("+")) {
      const minStr = rangeRaw.slice(0, -1).trim();
      const parsedMin = Number(minStr);
      if (!Number.isFinite(parsedMin)) {
        return [];
      }
      min = parsedMin;
      max = Infinity;
    } else if (rangeRaw.includes("-")) {
      const [minStrRaw, maxStrRaw] = rangeRaw.split("-", 2);
      const minStr = minStrRaw.trim();
      const maxStr = maxStrRaw.trim();

      if (minStr !== "") {
        const parsedMin = Number(minStr);
        if (!Number.isFinite(parsedMin)) {
          return [];
        }
        min = parsedMin;
      }

      if (maxStr !== "") {
        const parsedMax = Number(maxStr);
        if (!Number.isFinite(parsedMax)) {
          return [];
        }
        max = parsedMax;
      }
    } else {
      const exact = Number(rangeRaw);
      if (!Number.isFinite(exact)) {
        return [];
      }
      min = exact;
      max = exact;
    }

    return markers.filter((m) => {
      const t = Array.isArray(m) ? m[4] : m.travelTimeMinutes;
      if (t == null) return false;
      const num = typeof t === "number" ? t : Number(t);
      if (!Number.isFinite(num)) return false;
      return num >= min && num < max;
    });
  }

  applyFilter(e) {
    const btn = e.currentTarget;
    const range = btn.dataset.range;
    if (!range) return;
    this.currentRange = range;
    this.filterBtnTargets.forEach((b) => {
      b.classList.remove("active");
      b.setAttribute("aria-pressed", "false");
    });
    btn.classList.add("active");
    btn.setAttribute("aria-pressed", "true");
    this.rebuildClusterLayers();
  }

  rebuildClusterLayers() {
    this._removeClusterGroups();

    const areaData = this.getFilteredMarkers();
    const rockData = this.getFilteredRockMarkers();
    const hasRocks = rockData.length > 0;

    const pinIcon = createMainMapPinIcon();

    if (!hasRocks) {
      this.areaSummaryLayer = null;
      this.rockClusterGroup = null;
      this.areaClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 70,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        disableClusteringAtZoom: 9,
        iconCreateFunction: createRockGeoClusterIcon,
      });
      areaData.forEach((m) => {
        const lat = this._getCoord(m, 0);
        const lng = this._getCoord(m, 1);
        if (lat == null || lng == null || Number.isNaN(lat) || Number.isNaN(lng)) return;
        const popup = Array.isArray(m) ? (m[2] ?? "") : (m.popup ?? "");
        this.areaClusterGroup.addLayer(L.marker([lat, lng], { icon: pinIcon }).bindPopup(popup));
      });
      this._hasRocksForLayer = false;
      this.syncClusterLayerVisibility();
      return;
    }

    // Low zoom: each area is a bubble (rock count); nearby areas may merge (sum of rocks)
    this.areaClusterGroup = L.markerClusterGroup({
      maxClusterRadius: 72,
      spiderfyOnMaxZoom: true,
      showCoverageOnHover: false,
      disableClusteringAtZoom: 9,
      iconCreateFunction: createMergedAreaRockCountIcon,
    });
    addAreaBubbleMarkers(areaData, this.areaClusterGroup);

    // Mid zoom: one bubble per area only (clusters “separate” by area)
    this.areaSummaryLayer = L.layerGroup();
    addAreaBubbleMarkers(areaData, this.areaSummaryLayer);

    this.rockClusterGroup = L.markerClusterGroup({
      maxClusterRadius: ROCK_CLUSTER_MAX_RADIUS,
      spiderfyOnMaxZoom: true,
      showCoverageOnHover: false,
      disableClusteringAtZoom: ROCK_DISABLE_CLUSTERING_AT_ZOOM,
      iconCreateFunction: createRockGeoClusterIcon,
    });

    rockData.forEach((m) => {
      const lat = this._getCoord(m, 0);
      const lng = this._getCoord(m, 1);
      if (lat == null || lng == null || Number.isNaN(lat) || Number.isNaN(lng)) return;
      const popup = Array.isArray(m) ? (m[2] ?? "") : (m.popup ?? "");
      const marker = L.marker([lat, lng], { icon: pinIcon }).bindPopup(popup);
      this.rockClusterGroup.addLayer(marker);
    });

    this._hasRocksForLayer = true;
    this.syncClusterLayerVisibility();
  }

  syncClusterLayerVisibility() {
    const z = this.map.getZoom();
    const hasRocks = this._hasRocksForLayer;

    if (!hasRocks) {
      // No geolocated rocks: keep overview as mergeable area bubbles
      this._showOnlyLayer(this.areaClusterGroup);
      return;
    }

    if (z < Z_AREA_SEPARATE_MIN) {
      this._showOnlyLayer(this.areaClusterGroup);
    } else if (z < Z_ROCKS_MIN) {
      this._showOnlyLayer(this.areaSummaryLayer);
    } else {
      this._showOnlyLayer(this.rockClusterGroup);
    }
  }

  _showOnlyLayer(active) {
    const layers = [this.areaClusterGroup, this.areaSummaryLayer, this.rockClusterGroup];
    layers.forEach((g) => {
      if (!g) return;
      if (g === active) {
        if (!this.map.hasLayer(g)) this.map.addLayer(g);
      } else if (this.map.hasLayer(g)) {
        this.map.removeLayer(g);
      }
    });
  }

  _getCoord(m, index) {
    return _getCoordStatic(m, index);
  }
}

import { Controller } from "@hotwired/stimulus";
import L from "leaflet";
import "leaflet.markercluster";
import { createMapPinIcon, rockMarkerClusterGroupOptions } from "../map/icons.js";

const INFORMATION_ICON_PATHS = {
  bed: '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
  train: '<rect width="16" height="16" x="4" y="3" rx="2"/><path d="M4 11h16M12 3v8M8 19l-2 3M16 19l2 3M8 15h.01M16 15h.01"/>',
  waves: '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5S12 7 14.5 7 17 5 19.5 5c1.3 0 1.9.5 2.5 1M2 12c.6.5 1.2 1 2.5 1C7 13 7 11 9.5 11s2.5 2 5 2 2.5-2 5-2c1.3 0 1.9.5 2.5 1M2 18c.6.5 1.2 1 2.5 1C7 19 7 17 9.5 17s2.5 2 5 2 2.5-2 5-2c1.3 0 1.9.5 2.5 1"/>',
  landmark: '<path d="M3 22h18M6 18v-7M10 18v-7M14 18v-7M18 18v-7M12 2l9 5H3z"/>',
};

function createInformationIcon(name) {
  const paths = INFORMATION_ICON_PATHS[name] || INFORMATION_ICON_PATHS.landmark;
  return L.divIcon({
    html: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`,
    className: "railway-station-icon",
    iconSize: [40, 40],
    iconAnchor: [20, 20],
  });
}

function appendPopupDetail(content, label, value) {
  if (typeof value !== "string" || !value.trim()) return;

  const row = document.createElement("p");
  row.className = "mt-1 text-sm";
  const heading = document.createElement("span");
  heading.className = "font-medium";
  heading.textContent = `${label}: `;
  const valueElement = document.createElement("span");
  valueElement.className = "whitespace-pre-line";
  valueElement.textContent = value.trim();
  row.append(heading, valueElement);
  content.appendChild(row);
}

function createInformationPopup(point, labels) {
  const wrapper = document.createElement("div");
  wrapper.className = "w-[250px] overflow-visible rounded-xl";
  const content = document.createElement("div");
  content.className = "px-3.5 py-3";
  const heading = document.createElement("h3");
  heading.className = "mb-2 text-base font-semibold";
  heading.textContent = (typeof point.name === "string" && point.name.trim()) || labels.category;
  content.appendChild(heading);

  appendPopupDetail(content, labels.address, point.address);
  appendPopupDetail(content, labels.description, point.description);
  appendPopupDetail(content, labels.openingHours, point.openingHours);

  if (typeof point.phone === "string" && point.phone.trim()) {
    const phone = document.createElement("p");
    phone.className = "mt-1 text-sm";
    const phoneLabel = document.createElement("span");
    phoneLabel.className = "font-medium";
    phoneLabel.textContent = `${labels.phone}: `;
    const phoneLink = document.createElement("a");
    phoneLink.href = `tel:${point.phone.trim().replace(/[^+\d*#,;]/g, "")}`;
    phoneLink.textContent = point.phone.trim();
    phone.append(phoneLabel, phoneLink);
    content.appendChild(phone);
  }

  if (typeof point.link === "string" && point.link.trim()) {
    let externalUrl;
    try {
      const parsedUrl = new URL(point.link.trim(), window.location.origin);
      if (["http:", "https:"].includes(parsedUrl.protocol)) externalUrl = parsedUrl.href;
    } catch {
      // Ignore malformed URLs from the editable infrastructure JSON.
    }
    if (externalUrl) {
      const link = document.createElement("a");
      link.className = "mt-2 inline-block break-all text-sm underline underline-offset-2";
      link.href = externalUrl;
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      link.textContent = (typeof point.linkLabel === "string" && point.linkLabel.trim()) || labels.website;
      content.appendChild(link);
    }
  }

  wrapper.appendChild(content);
  return wrapper;
}

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["map", "layerBtn", "attrFilterBtn"];

  connect() {
    this._cleanupExistingMap();
    const markersArea = JSON.parse(this.data.get("markersArea"));
    const zoom = JSON.parse(this.data.get("zoom"));
    const information = JSON.parse(this.data.get("railwayStations")) || {};
    this._mapOwner = Symbol("area-map-owner");
    this.areaMap = L.map(this.mapTarget).setView([zoom[0], zoom[1]], zoom[2]);
    this.mapTarget.__leafletMapInstance = this.areaMap;
    this.mapTarget.__leafletMapOwner = this._mapOwner;
    this.rocksLayerVisible = true;

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18,
    }).addTo(this.areaMap);

    this.informationMarkers = new Map();
    this.layerBtnTargets.forEach((btn) => {
      const layer = btn.dataset.layer;
      if (!layer || layer === "rocks") return;

      const keys = (btn.dataset.informationKeys || "").split(",").filter(Boolean);
      const points = keys.flatMap((key) => {
        const locations = information[key];
        if (Array.isArray(locations)) return locations;
        return locations && typeof locations === "object" ? [locations] : [];
      });
      const labels = {
        category: btn.dataset.popupLabel || "Information",
        address: btn.dataset.popupAddressLabel || "Address",
        description: btn.dataset.popupDescriptionLabel || "Details",
        openingHours: btn.dataset.popupOpeningHoursLabel || "Opening hours",
        phone: btn.dataset.popupPhoneLabel || "Phone",
        website: btn.dataset.popupLinkLabel || "Website",
      };
      const markers = points.flatMap((point) => {
        const lat = Number(point?.lat);
        const lng = Number(point?.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return [];

        const marker = L.marker([lat, lng], { icon: createInformationIcon(btn.dataset.markerIcon) }).bindPopup(createInformationPopup(point, labels));
        if (btn.classList.contains("active")) marker.addTo(this.areaMap);
        return [marker];
      });
      this.informationMarkers.set(layer, markers);
    });

    this.rocksClusterGroup = L.markerClusterGroup(rockMarkerClusterGroupOptions());

    this.areaMarkers = [];
    this.areaMarkerData = [];
    markersArea.forEach((markerData) => {
      const lat = parseFloat(markerData[0]);
      const lng = parseFloat(markerData[1]);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
      }
      const marker = L.marker([lat, lng], { icon: createMapPinIcon() }).bindPopup(markerData[2]);
      this.areaMarkers.push(marker);
      this.areaMarkerData.push(markerData);
      this.rocksClusterGroup.addLayer(marker);
    });

    this.areaMap.addLayer(this.rocksClusterGroup);
    this.applyRockVisibility();

    if (this.hasLayerBtnTarget) {
      this.layerBtnTargets.forEach((btn) => {
        btn.addEventListener("click", (e) => this.toggleLayer(e));
      });
    }
    if (this.hasAttrFilterBtnTarget) {
      this.attrFilterBtnTargets.forEach((btn) => {
        btn.addEventListener("click", (e) => this.toggleAttrFilter(e));
      });
    }

    const dialog = this.element.closest("dialog");
    if (dialog) {
      this._dialogOpenObserver = new MutationObserver(() => {
        if (dialog.open) {
          if (this._invalidateSizeTimeout) {
            clearTimeout(this._invalidateSizeTimeout);
          }
          this._invalidateSizeTimeout = setTimeout(() => this._safeInvalidateSize(), 40);
        }
      });
      this._dialogOpenObserver.observe(dialog, {
        attributes: true,
        attributeFilter: ["open"],
      });
    }
  }

  disconnect() {
    if (this._invalidateSizeTimeout) {
      clearTimeout(this._invalidateSizeTimeout);
      this._invalidateSizeTimeout = null;
    }

    const ownsContainerMap =
      this.mapTarget &&
      this.mapTarget.__leafletMapInstance === this.areaMap &&
      this.mapTarget.__leafletMapOwner === this._mapOwner;

    if (this.areaMap) {
      try {
        this.areaMap.eachLayer((layer) => {
          this.areaMap.removeLayer(layer);
        });
      } catch {
        // Ignore teardown races; map may already be partially removed.
      }
      if (ownsContainerMap) {
        try {
          this.areaMap.remove();
        } catch {
          // Another controller may have already replaced/removed this map instance.
        }
      }
      this.areaMap = null;
    }

    if (this.mapTarget && ownsContainerMap) {
      this.mapTarget.__leafletMapInstance = null;
      this.mapTarget.__leafletMapOwner = null;
      if ("_leaflet_id" in this.mapTarget) {
        this.mapTarget._leaflet_id = null;
      }
    }

    this._dialogOpenObserver?.disconnect();
  }

  _safeInvalidateSize() {
    const map = this.areaMap;
    if (!map || !map._container || !map._mapPane) {
      return;
    }
    try {
      map.invalidateSize();
    } catch {
      // Map can be torn down between observer tick and callback.
    }
  }

  _cleanupExistingMap() {
    const existing = this.mapTarget?.__leafletMapInstance;
    if (existing) {
      try {
        existing.remove();
      } catch {
        // Ignore stale teardown races; we reset container markers below.
      }
      this.mapTarget.__leafletMapInstance = null;
      this.mapTarget.__leafletMapOwner = null;
    }
    if (this.mapTarget && "_leaflet_id" in this.mapTarget) {
      this.mapTarget._leaflet_id = null;
    }
  }

  toggleLayer(e) {
    const btn = e.currentTarget;
    const layer = btn.dataset.layer;
    btn.classList.toggle("active");
    btn.setAttribute("aria-pressed", btn.classList.contains("active"));
    if (layer === "rocks") {
      this.rocksLayerVisible = btn.classList.contains("active");
      this.applyRockVisibility();
    } else {
      const visible = btn.classList.contains("active");
      const markers = this.informationMarkers?.get(layer);
      if (markers) {
        markers.forEach((marker) => {
          if (visible) marker.addTo(this.areaMap);
          else this.areaMap.removeLayer(marker);
        });
      }
    }
  }

  toggleAttrFilter(e) {
    const btn = e.currentTarget;
    btn.classList.toggle("active");
    btn.setAttribute("aria-pressed", btn.classList.contains("active"));
    this.applyRockVisibility();
  }

  applyRockVisibility() {
    const attrActive = {};
    if (this.hasAttrFilterBtnTarget) {
      this.attrFilterBtnTargets.forEach((btn) => {
        const attr = btn.dataset.attr;
        if (attr) attrActive[attr] = btn.classList.contains("active");
      });
    }

    if (!this.rocksClusterGroup) {
      return;
    }

    if (!this.rocksLayerVisible) {
      if (this.areaMap.hasLayer(this.rocksClusterGroup)) {
        this.areaMap.removeLayer(this.rocksClusterGroup);
      }
      return;
    }

    if (!this.areaMap.hasLayer(this.rocksClusterGroup)) {
      this.areaMap.addLayer(this.rocksClusterGroup);
    }

    this.areaMarkers.forEach((marker, i) => {
      const data = this.areaMarkerData[i];
      if (!data) {
        if (this.rocksClusterGroup.hasLayer(marker)) {
          this.rocksClusterGroup.removeLayer(marker);
        }
        return;
      }
      const childFriendly = !!data[4];
      const sunny = !!data[5];
      const rain = !!data[6];
      const train = !!data[7];
      const bike = !!data[8];
      let show = true;
      if (attrActive.childFriendly && !childFriendly) show = false;
      if (attrActive.sunny && !sunny) show = false;
      if (attrActive.rain && !rain) show = false;
      if (attrActive.train && attrActive.bike) {
        if (!(train && bike)) show = false;
      } else {
        if (attrActive.train && !train) show = false;
        if (attrActive.bike && !bike) show = false;
      }
      if (show) {
        if (!this.rocksClusterGroup.hasLayer(marker)) {
          this.rocksClusterGroup.addLayer(marker);
        }
      } else if (this.rocksClusterGroup.hasLayer(marker)) {
        this.rocksClusterGroup.removeLayer(marker);
      }
    });
  }
}

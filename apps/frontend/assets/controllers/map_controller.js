import { Controller } from "@hotwired/stimulus";
import L from "leaflet";
import "leaflet.markercluster";
import { createMapPinIcon, rockMarkerClusterGroupOptions } from "../map/icons.js";

function createInformationIcon(sourceIcon) {
  if (!sourceIcon) return null;

  const icon = sourceIcon.cloneNode(true);
  icon.classList.remove("size-4");
  icon.classList.add("size-6");
  icon.setAttribute("width", "24");
  icon.setAttribute("height", "24");

  return L.divIcon({
    html: icon.outerHTML,
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

        const markerIcon = createInformationIcon(btn.querySelector("svg"));
        if (!markerIcon) return [];
        const marker = L.marker([lat, lng], { icon: markerIcon }).bindPopup(createInformationPopup(point, labels));
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

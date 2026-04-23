import { Controller } from "stimulus";
import L from "leaflet";
import "leaflet.markercluster";

const ROCK_CLUSTER_MAX_RADIUS = 48;
const ROCK_DISABLE_CLUSTERING_AT_ZOOM = 15;

const PIN_SVG =
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 40" width="28" height="40" aria-hidden="true">' +
  '<path fill="#ef4444" stroke="#b91c1c" stroke-width="0.75" d="M14 .5C6.5.5.5 6.4.5 13.9c0 5.2 2.6 10.4 6.5 15.8 3.2 4.4 7 8.3 7 8.3s3.8-3.9 7-8.3c3.9-5.4 6.5-10.6 6.5-15.8C27.5 6.4 21.5.5 14 .5z"/>' +
  '<circle cx="14" cy="14" r="4.25" fill="#fff"/>' +
  "</svg>";

function createMapPinIcon() {
  return L.divIcon({
    html: PIN_SVG,
    className: "main-map-pin-icon",
    iconSize: [28, 40],
    iconAnchor: [14, 40],
    popupAnchor: [0, -34],
  });
}

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

function createRockGeoClusterIcon(cluster) {
  return bubbleIconForCount(cluster.getChildCount());
}

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["map", "layerBtn", "attrFilterBtn"];

  connect() {
    const markersArea = JSON.parse(this.data.get("markersArea"));
    const zoom = JSON.parse(this.data.get("zoom"));
    const information = JSON.parse(this.data.get("railwayStations"));
    this.areaMap = L.map(this.mapTarget).setView([zoom[0], zoom[1]], zoom[2]);
    this.rocksLayerVisible = true;

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18,
    }).addTo(this.areaMap);

    const trainStationIcon = L.divIcon({
      html: `<svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c-4 0-8 .5-8 4v9.5A3.5 3.5 0 0 0 7.5 19L6 20.5v.5h2.23l2-2H14l2 2h2v-.5L16.5 19a3.5 3.5 0 0 0 3.5-3.5V6c0-3.5-3.58-4-8-4M7.5 17A1.5 1.5 0 0 1 6 15.5A1.5 1.5 0 0 1 7.5 14A1.5 1.5 0 0 1 9 15.5A1.5 1.5 0 0 1 7.5 17m3.5-7H6V6h5zm2 0V6h5v4zm3.5 7a1.5 1.5 0 0 1-1.5-1.5a1.5 1.5 0 0 1 1.5-1.5a1.5 1.5 0 0 1-1.5 1.5"/></svg>`,
      className: "railway-station-icon",
      iconSize: [40, 40],
      iconAnchor: [20, 20],
    });

    const campingIcon = L.divIcon({
      html: `<svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M19 7h-8v7H3V5H1v15h2v-3h18v3h2v-9a4 4 0 0 0-4-4M7 13a3 3 0 0 0 3-3a3 3 0 0 0-3-3a3 3 0 0 0-3 3a3 3 0 0 0 3 3"/></svg>`,
      className: "railway-station-icon",
      iconSize: [40, 40],
      iconAnchor: [20, 20],
    });

    this.trainMarkers = [];
    if (information.trainStations && information.trainStations.length > 0) {
      information.trainStations.forEach((trainStation) => {
        if (trainStation) {
          const latLng = [trainStation.lat, trainStation.lng];
          const marker = L.marker(latLng, { icon: trainStationIcon })
            .bindPopup(
              `<div class="main-map-popup-card main-map-popup-card--text-only"><div class="main-map-popup-body"><h3 class="fs-6 mb-2">Haltestelle ${trainStation.name}</h3>${
                trainStation.link
                  ? `<a href="${trainStation.link}" target="_blank" rel="noopener noreferrer">Link zum Fahrplan</a>`
                  : ""
              }</div></div>`
            )
            .addTo(this.areaMap);
          this.trainMarkers.push(marker);
        }
      });
    }

    this.campingMarkers = [];
    if (information.campingSites && information.campingSites.length > 0) {
      information.campingSites.forEach((campingSite) => {
        if (campingSite) {
          const latLng = [campingSite.lat, campingSite.lng];
          const marker = L.marker(latLng, { icon: campingIcon })
            .bindPopup(
              `<div class="main-map-popup-card main-map-popup-card--text-only"><div class="main-map-popup-body"><h3 class="fs-6 mb-2">Campingplatz ${campingSite.name}</h3><a href="${campingSite.link}" target="_blank" rel="noopener noreferrer">${campingSite.link}</a></div></div>`
            )
            .addTo(this.areaMap);
          this.campingMarkers.push(marker);
        }
      });
    }

    const pinIcon = createMapPinIcon();
    this.rocksClusterGroup = L.markerClusterGroup({
      maxClusterRadius: ROCK_CLUSTER_MAX_RADIUS,
      spiderfyOnMaxZoom: true,
      showCoverageOnHover: false,
      disableClusteringAtZoom: ROCK_DISABLE_CLUSTERING_AT_ZOOM,
      iconCreateFunction: createRockGeoClusterIcon,
    });

    this.areaMarkers = [];
    this.areaMarkerData = [];
    markersArea.forEach((markerData) => {
      const lat = parseFloat(markerData[0]);
      const lng = parseFloat(markerData[1]);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
      }
      const marker = L.marker([lat, lng], { icon: pinIcon }).bindPopup(markerData[2]);
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
          setTimeout(() => this.areaMap.invalidateSize(), 40);
        }
      });
      this._dialogOpenObserver.observe(dialog, {
        attributes: true,
        attributeFilter: ["open"],
      });
    }
  }

  disconnect() {
    this._dialogOpenObserver?.disconnect();
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
      const markers = layer === "railway" ? this.trainMarkers : layer === "camping" ? this.campingMarkers : null;
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

import { Controller } from "stimulus";
import L from "leaflet";

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

    // Create a custom icon
    const trainStationIcon = L.divIcon({
      html: `<svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c-4 0-8 .5-8 4v9.5A3.5 3.5 0 0 0 7.5 19L6 20.5v.5h2.23l2-2H14l2 2h2v-.5L16.5 19a3.5 3.5 0 0 0 3.5-3.5V6c0-3.5-3.58-4-8-4M7.5 17A1.5 1.5 0 0 1 6 15.5A1.5 1.5 0 0 1 7.5 14A1.5 1.5 0 0 1 9 15.5A1.5 1.5 0 0 1 7.5 17m3.5-7H6V6h5zm2 0V6h5v4zm3.5 7a1.5 1.5 0 0 1-1.5-1.5a1.5 1.5 0 0 1 1.5-1.5a1.5 1.5 0 0 1-1.5 1.5"/></svg>`,
      className: "railway-station-icon",
      iconSize: [40, 40], // Adjust size to include padding
      iconAnchor: [20, 20], // Center the icon
    });

    const campingIcon = L.divIcon({
      html: `<svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M19 7h-8v7H3V5H1v15h2v-3h18v3h2v-9a4 4 0 0 0-4-4M7 13a3 3 0 0 0 3-3a3 3 0 0 0-3-3a3 3 0 0 0-3 3a3 3 0 0 0 3 3"/></svg>`,
      className: "railway-station-icon",
      iconSize: [40, 40], // Adjust size to include padding
      iconAnchor: [20, 20], // Center the icon
    });

    // Add railway station markers
    this.trainMarkers = [];
    if (information.trainStations && information.trainStations.length > 0) {
      information.trainStations.forEach((trainStation) => {
        if (trainStation) {
          const latLng = [trainStation.lat, trainStation.lng];
          const marker = L.marker(latLng, { icon: trainStationIcon })
            .bindPopup(
              `<b>Haltestelle ${trainStation.name}</b><br>${
                trainStation.link
                  ? `<a href="${trainStation.link}" target="_blank">Link zum Fahrplan</a>`
                  : ""
              }`
            )
            .addTo(this.areaMap);
          this.trainMarkers.push(marker);
        }
      });
    }
    // Add camping site markers
    this.campingMarkers = [];
    if (information.campingSites && information.campingSites.length > 0) {
      information.campingSites.forEach((campingSite) => {
        if (campingSite) {
          const latLng = [campingSite.lat, campingSite.lng];
          const marker = L.marker(latLng, { icon: campingIcon })
            .bindPopup(
              `<b>Campingplatz ${campingSite.name}</b><br><a href="${campingSite.link}" target="_blank">${campingSite.link}</a>`
            )
            .addTo(this.areaMap);
          this.campingMarkers.push(marker);
        }
      });
    }

    // Add area markers (markerData: [lat, lng, popup, rockImage, childFriendly, sunny, rain, train, bike])
    this.areaMarkers = [];
    this.areaMarkerData = [];
    markersArea.forEach((markerData) => {
      const marker = L.marker([markerData[0], markerData[1]])
        .bindPopup(markerData[2]);
      this.areaMarkers.push(marker);
      this.areaMarkerData.push(markerData);
    });
    this.applyRockVisibility();

    // Layer filter buttons
    if (this.hasLayerBtnTarget) {
      this.layerBtnTargets.forEach((btn) => {
        btn.addEventListener("click", (e) => this.toggleLayer(e));
      });
    }
    // Attribute filter buttons (childFriendly, sunny, rain, train, bike)
    if (this.hasAttrFilterBtnTarget) {
      this.attrFilterBtnTargets.forEach((btn) => {
        btn.addEventListener("click", (e) => this.toggleAttrFilter(e));
      });
    }

    // Resize map when modal is shown
    const modal = this.element.closest(".modal");
    if (modal) {
      modal.addEventListener("shown.bs.modal", () => {
        setTimeout(() => this.areaMap.invalidateSize(), 40);
      });
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
      let markers = layer === "railway" ? this.trainMarkers : layer === "camping" ? this.campingMarkers : null;
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
    const anyAttrFilter = Object.values(attrActive).some((v) => v);
    this.areaMarkers.forEach((marker, i) => {
      const data = this.areaMarkerData[i];
      if (!data || !this.rocksLayerVisible) {
        this.areaMap.removeLayer(marker);
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
      if (show) marker.addTo(this.areaMap);
      else this.areaMap.removeLayer(marker);
    });
  }
}

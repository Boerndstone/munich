import { Controller } from "@hotwired/stimulus";
import L from "leaflet";
import "leaflet-draw"; // Import Leaflet Draw

export default class extends Controller {
  static targets = ["map", "exportButton", "coordinates"];
  static values = {
    lat: { type: Number, default: 49.01 },
    lng: { type: Number, default: 11.95 },
    zoom: { type: Number, default: 16 },
    coordinates: { type: Array, default: [] },
  };

  connect() {
    console.log("Admin Draw Map controller connected!");
    // Check if Leaflet and Leaflet Draw are loaded
    if (typeof L === "undefined") {
      console.error("Leaflet did not load.");
      return;
    }

    if (typeof L.Control.Draw === "undefined") {
      console.error("Leaflet Draw did not load.");
      return;
    }

    // Initialize the map with configurable center and zoom
    this.map = L.map(this.mapTarget).setView(
      [this.latValue, this.lngValue],
      this.zoomValue
    );

    // Add the tile layer
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
    }).addTo(this.map);

    // Create a FeatureGroup to store editable layers
    this.featureGroup = new L.FeatureGroup();
    this.map.addLayer(this.featureGroup);

    // Load existing coordinates if available
    this.loadExistingCoordinates();

    // Add the draw control and link it to the FeatureGroup
    const drawControl = new L.Control.Draw({
      draw: {
        polyline: true,
        polygon: true,
        rectangle: true,
        circle: true,
        marker: true,
      },
      edit: {
        featureGroup: this.featureGroup,
      },
    });
    this.map.addControl(drawControl);

    // Handle the creation of new layers
    this.map.on("draw:created", (event) => {
      const layer = event.layer;
      this.featureGroup.addLayer(layer);
      this.updateCoordinatesField();
    });

    // Handle edits
    this.map.on("draw:edited", () => {
      this.updateCoordinatesField();
    });

    // Handle deletions
    this.map.on("draw:deleted", () => {
      this.updateCoordinatesField();
    });
  }

  loadExistingCoordinates() {
    const coordinates = this.coordinatesValue;

    if (!coordinates || !Array.isArray(coordinates) || coordinates.length === 0) {
      return;
    }

    coordinates.forEach((item) => {
      if (!item || !item.type || !item.coordinates) {
        return;
      }

      try {
        switch (item.type) {
          case "Point":
            const point = item.coordinates;
            if (Array.isArray(point) && point.length >= 2) {
              const marker = L.marker([point[1], point[0]]);
              this.featureGroup.addLayer(marker);
            }
            break;

          case "LineString":
            const lineCoords = item.coordinates.map((coord) => [
              coord[1],
              coord[0],
            ]);
            if (lineCoords.length > 0) {
              const polyline = L.polyline(lineCoords, {
                color: "#3388ff",
                weight: 4,
              });
              this.featureGroup.addLayer(polyline);
            }
            break;

          case "Polygon":
            const polyCoords = item.coordinates[0]?.map((coord) => [
              coord[1],
              coord[0],
            ]);
            if (polyCoords && polyCoords.length > 0) {
              const polygon = L.polygon(polyCoords, {
                color: "#3388ff",
                weight: 2,
              });
              this.featureGroup.addLayer(polygon);
            }
            break;
        }
      } catch (e) {
        console.warn("Error loading coordinate:", e);
      }
    });

    // Fit map to show all features if there are any
    if (this.featureGroup.getLayers().length > 0) {
      try {
        this.map.fitBounds(this.featureGroup.getBounds(), { padding: [50, 50] });
      } catch (e) {
        console.warn("Could not fit bounds:", e);
      }
    }
  }

  updateCoordinatesField() {
    const drawings = [];

    this.featureGroup.eachLayer((layer) => {
      const geoJson = layer.toGeoJSON();
      drawings.push({
        type: geoJson.geometry.type,
        coordinates: geoJson.geometry.coordinates,
      });
    });

    if (this.hasCoordinatesTarget) {
      this.coordinatesTarget.value = JSON.stringify(drawings, null, 2);
    }
  }

  exportDrawings() {
    const drawings = [];

    this.featureGroup.eachLayer((layer) => {
      const drawingInfo = {
        type: layer.toGeoJSON().geometry.type,
        coordinates: layer.toGeoJSON().geometry.coordinates,
      };
      drawings.push(drawingInfo);
    });

    if (this.hasCoordinatesTarget) {
      this.coordinatesTarget.value = JSON.stringify(drawings, null, 2);
    }
  }
}

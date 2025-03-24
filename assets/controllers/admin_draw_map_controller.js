import { Controller } from "stimulus";
import L from "leaflet";
import "leaflet-draw"; // Import Leaflet Draw

export default class extends Controller {
  static targets = ["map", "exportButton", "coordinates"];

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

    // Initialize the map
    this.map = L.map(this.mapTarget).setView([49.01, 11.95], 16);

    // Add the tile layer
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
    }).addTo(this.map);

    // Create a FeatureGroup to store editable layers
    this.featureGroup = new L.FeatureGroup();
    this.map.addLayer(this.featureGroup);

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
    });
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

    coordinates.value = JSON.stringify(drawings);
  }
}
